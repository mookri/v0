<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\ClientAssertionVerifier;
use App\Platform\Identity\OAuth\ClientAuthKey;
use App\Platform\Identity\OAuth\Jwk;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Identity\OAuth\RsaKeyGenerator;
use App\Platform\Identity\OAuth\TokenExchangeGrant;
use App\Platform\Identity\PassportJwtVerifier;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RsaSha256;
use Lcobucci\JWT\Token\Builder;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * RFC 8693 token exchange (ADR-0020 P6/D1/D3/D4/D5): a registered
 * exchange client hands over a subject token (optionally its own actor
 * token) and receives a fresh access token bound to itself carrying the
 * same human subject. Sole-issuer verification, server-chosen audience,
 * scope narrowing, row-authoritative subject identity, and a
 * hash-chained allow/deny record for every authorization outcome.
 */
final class TokenExchangeTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    private const EXCHANGE_SECRET = 'exchange-secret-1';

    private const MINT = TokenExchangeGrant::GRANT_IDENTIFIER;

    private const ACCESS = TokenExchangeGrant::TOKEN_TYPE;

    /**
     * Confidential first-party service client registered for the
     * exchange on BOTH enforcement surfaces (client row + profile) —
     * the BFF machine shape.
     */
    private function exchangeClient(
        string $name = 'Exchange BFF',
        ?string $audience = null,
        array $rowGrants = ['client_credentials', self::MINT],
        array $profileGrants = ['client_credentials', self::MINT],
        string $clientType = OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE,
    ): Client {
        $client = new Client();
        $client->forceFill([
            'name' => $name,
            'secret' => self::EXCHANGE_SECRET,
            'redirect_uris' => [],
            'grant_types' => $rowGrants,
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => $clientType,
            'allowed_grants' => $profileGrants,
            'audience' => $audience,
        ]);

        return $client;
    }

    private function subjectFor(User $user): string
    {
        return $this->userTokensFor($user)[1]['access_token'];
    }

    private function exchange(Client $client, string $subjectToken, array $extra = []): TestResponse
    {
        return $this->postJson('/oauth/token', array_merge([
            'grant_type' => self::MINT,
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::EXCHANGE_SECRET,
            'subject_token' => $subjectToken,
            'subject_token_type' => self::ACCESS,
        ], $extra));
    }

    private function clientCredentialsToken(Client $client): string
    {
        return $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::EXCHANGE_SECRET,
        ])->assertOk()->json('access_token');
    }

    public function test_exchange_issues_an_access_token_bounded_to_the_exchange_client(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $subjectJti = (string) PassportJwtVerifier::verify($subject, time())['jti'];
        $client = $this->exchangeClient();

        $token = $this->exchange($client, $subject)->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('issued_token_type', self::ACCESS)
            ->assertJsonMissingPath('refresh_token')
            ->json();

        $claims = PassportJwtVerifier::verify($token['access_token'], time());
        $this->assertNotNull($claims, 'The exchanged token must verify against the issuer key.');
        // The audience is the EXCHANGE client — league signs it from the
        // authenticated client, never from anything the requester said.
        $this->assertSame((string) $client->getKey(), $claims['aud']);
        $this->assertSame((string) $user->getAuthIdentifier(), $claims['sub']);
        $this->assertNotSame($subjectJti, $claims['jti']);

        $row = Passport::token()->newQuery()->find($claims['jti']);
        $this->assertNotNull($row);
        $this->assertSame((string) $client->getKey(), (string) $row->getAttribute('client_id'));
        $this->assertSame((string) $user->getAuthIdentifier(), (string) $row->getAttribute('user_id'));
        $this->assertFalse((bool) $row->getAttribute('revoked'));

        // The subject token is a delegation SOURCE, not a consumed
        // credential: exchange never revokes it.
        $subjectRow = Passport::token()->newQuery()->find($subjectJti);
        $this->assertNotNull($subjectRow);
        $this->assertFalse((bool) $subjectRow->getAttribute('revoked'));
    }

    public function test_exchange_is_hash_chained_and_correlated_with_the_issuance(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $client = $this->exchangeClient('Correlated BFF');

        $token = $this->exchange($client, $subject)->assertOk()->json();
        $claims = PassportJwtVerifier::verify($token['access_token'], time());
        $this->assertNotNull($claims);

        $exchanged = AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('token_jti_ref', AuthEventRecorder::ref('jti', $claims['jti']))
            ->firstOrFail();

        $this->assertSame('allow', $exchanged->getAttribute('decision'));
        $this->assertSame(self::MINT, $exchanged->getAttribute('grant_type'));
        $this->assertSame((string) $client->getKey(), $exchanged->getAttribute('actor_client_id'));
        $this->assertSame('subject_verified', $exchanged->getAttribute('reason_code'));
        $this->assertSame([], $exchanged->getAttribute('granted_scopes'));
        // source = the subject's issuing client (no profile label on the
        // web helper client → the client id itself); target = the
        // exchange client's registered identity.
        $this->assertNotNull($exchanged->getAttribute('source_audience'));
        $this->assertNotSame('', $exchanged->getAttribute('source_audience'));
        $this->assertSame((string) $client->getKey(), $exchanged->getAttribute('target_audience'));
        // Pseudonymous refs only: the subject column carries the HMAC
        // ref (prefixed, truncated), never the raw user id.
        $this->assertStringStartsWith('sub:', (string) $exchanged->getAttribute('subject_ref'));
        $this->assertNotSame((string) $user->getAuthIdentifier(), (string) $exchanged->getAttribute('subject_ref'));
        $this->assertStringNotContainsString($claims['jti'], (string) $exchanged->getAttribute('token_jti_ref'));

        // The issuance row shares the jti reference and links directly
        // onto the chain (issued first, exchange decision second).
        $issued = AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.issued')
            ->where('token_jti_ref', AuthEventRecorder::ref('jti', $claims['jti']))
            ->firstOrFail();
        $this->assertSame($exchanged->getAttribute('subject_ref'), $issued->getAttribute('subject_ref'));
        $this->assertSame((string) $client->getKey(), $issued->getAttribute('actor_client_id'));
        $this->assertSame($issued->getAttribute('hash'), $exchanged->getAttribute('prev_hash'));
    }

    public function test_unverifiable_subject_tokens_fail_closed(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $client = $this->exchangeClient();

        // Not a JWT at all.
        $this->exchange($client, 'garbage')
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        // Right shape, forged signature: the sole issuer's key check is
        // what stands between a copied payload and a delegated identity.
        $tampered = substr($subject, 0, -4).'AAAA';
        $this->exchange($client, $tampered)
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        $denies = AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'subject_token_unverifiable')
            ->count();
        $this->assertSame(2, $denies);
    }

    public function test_revoked_subject_token_is_refused(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $jti = (string) PassportJwtVerifier::verify($subject, time())['jti'];
        Passport::token()->newQuery()->whereKey($jti)->update(['revoked' => true]);

        $this->exchange($this->exchangeClient(), $subject)
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'subject_token_inactive')
            ->first());
    }

    public function test_machine_subject_tokens_carry_no_delegable_identity(): void
    {
        $machine = $this->clientCredentialsToken($this->exchangeClient('Machine BFF'));

        // The subject row has no user: sub here is a client id that
        // could collide with another table's ids — refused, never
        // repurposed as a human subject.
        $this->exchange($this->exchangeClient('Delegating BFF'), $machine)
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'subject_not_user_context')
            ->first());
    }

    public function test_inactive_subject_user_is_refused(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $user->forceFill(['status' => 'suspended'])->save();

        $this->exchange($this->exchangeClient(), $subject)
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'subject_inactive_user')
            ->first());
    }

    public function test_revoked_issuing_client_is_not_a_delegation_source(): void
    {
        $user = User::factory()->create();
        [$webClient, $tokens] = $this->userTokensFor($user);
        $webClient->forceFill(['revoked' => true])->save();

        $this->exchange($this->exchangeClient(), $tokens['access_token'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'subject_client_revoked')
            ->first());
    }

    public function test_unregistered_audiences_are_refused(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $client = $this->exchangeClient();

        // RFC 8693 invalid_target for an audience nobody registered to
        // this client (D1: request, never select).
        $this->exchange($client, $subject, ['audience' => 'https://evil.example/api'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_target');

        // Ambiguous selectors are rejected, not resolved by fiat.
        $this->exchange($client, $subject, ['audience' => self::ACCESS, 'resource' => self::ACCESS])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_request');

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'audience_not_registered')
            ->first());
    }

    public function test_registered_audience_is_honored_without_leaking_into_claims(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $client = $this->exchangeClient('Labeled BFF', audience: 'platform');

        // The registered label may be asked for…
        $token = $this->exchange($client, $subject, ['audience' => 'platform'])
            ->assertOk()->json();

        // …and the client's own id may always be asked for…
        $this->exchange($client, $subject, ['audience' => (string) $client->getKey()])
            ->assertOk();

        // …but the claim stays the server-chosen client binding: the
        // label never enters the JWT (D1).
        $claims = PassportJwtVerifier::verify($token['access_token'], time());
        $this->assertNotNull($claims);
        $this->assertSame((string) $client->getKey(), $claims['aud']);

        // The audit target carries the readable label.
        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'allow')
            ->where('target_audience', 'platform')
            ->first());
    }

    public function test_scope_may_narrow_but_never_widen(): void
    {
        Passport::tokensCan(['read' => 'Read access.']);

        try {
            $user = User::factory()->create();
            $subject = $this->subjectFor($user); // scopes: []
            $client = $this->exchangeClient();

            // 'read' is system-registered (the registry would accept it)
            // but is NOT in the subject token — the subset rule blocks
            // the widening attempt first, and it lands on the chain.
            $this->exchange($client, $subject, ['scope' => 'read'])
                ->assertStatus(400)
                ->assertJsonPath('error', 'invalid_scope');

            $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
                ->where('event_type', 'token.exchanged')
                ->where('decision', 'deny')
                ->where('reason_code', 'scope_not_subset')
                ->first());
        } finally {
            Passport::tokensCan([]);
        }
    }

    public function test_actor_token_must_be_authentic_and_bound_to_the_presenting_client(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $client = $this->exchangeClient();

        // Foreign actor: the subject token itself is bound to the web
        // client — this presenter can never stand inside that context.
        $this->exchange($client, $subject, [
            'actor_token' => $subject,
            'actor_token_type' => self::ACCESS,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'actor_token_foreign_client')
            ->first());

        // Unverifiable actor (forged signature).
        $this->exchange($client, $subject, [
            'actor_token' => substr($subject, 0, -4).'BBBB',
            'actor_token_type' => self::ACCESS,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'actor_token_invalid')
            ->first());

        // The client's OWN machine credential is a valid actor.
        $actor = $this->clientCredentialsToken($client);
        $this->exchange($client, $subject, [
            'actor_token' => $actor,
            'actor_token_type' => self::ACCESS,
        ])->assertOk();
    }

    public function test_exchange_requires_registration_on_both_client_surfaces(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);

        // Profile never allowed the grant: the per-client gate answers
        // before league even sees the request.
        $noProfileGrant = $this->exchangeClient(
            'Profile-less exchange BFF',
            rowGrants: ['client_credentials', self::MINT],
            profileGrants: ['client_credentials'],
        );
        $this->exchange($noProfileGrant, $subject)
            ->assertStatus(400)
            ->assertJsonPath('error', 'unsupported_grant');

        // Client row never allowed the grant: league's
        // supportsGrantType answers (400 unauthorized_client).
        $noRowGrant = $this->exchangeClient(
            'Row-less exchange BFF',
            rowGrants: ['client_credentials'],
            profileGrants: ['client_credentials', self::MINT],
        );
        $this->exchange($noRowGrant, $subject)
            ->assertStatus(400)
            ->assertJsonPath('error', 'unauthorized_client');
    }

    public function test_only_confidential_first_party_services_may_exchange(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);

        // Web-profiled client: right grants, wrong boundary — a human
        // surface never inherits subject identity across tokens.
        $web = $this->exchangeClient(
            'Web impersonator',
            rowGrants: ['client_credentials', self::MINT],
            profileGrants: ['client_credentials', self::MINT],
            clientType: OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
        );
        $this->exchange($web, $subject)
            ->assertStatus(400)
            ->assertJsonPath('error', 'unauthorized_client');

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'client_not_exchange_eligible')
            ->first());

        // Public client: nothing to authenticate the machine boundary
        // with — league passes it through, the grant refuses it.
        $public = new Client();
        $public->forceFill([
            'name' => 'Public pretender',
            'secret' => null,
            'redirect_uris' => ['https://example.test/callback'],
            'grant_types' => ['client_credentials', self::MINT],
            'revoked' => false,
        ]);
        $public->save();
        OAuthClientProfile::factory()->create([
            'client_id' => (string) $public->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE,
            'allowed_grants' => ['client_credentials', self::MINT],
        ]);

        $this->exchange($public, $subject)
            ->assertStatus(401)
            ->assertJsonPath('error', 'invalid_client');

        $this->assertNotNull(AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'token.exchanged')
            ->where('decision', 'deny')
            ->where('reason_code', 'client_not_confidential')
            ->first());
    }

    public function test_client_secret_authentication_still_guards_the_endpoint(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $client = $this->exchangeClient();

        // Missing secret for a confidential client.
        $this->postJson('/oauth/token', [
            'grant_type' => self::MINT,
            'client_id' => (string) $client->getKey(),
            'subject_token' => $subject,
            'subject_token_type' => self::ACCESS,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        // Wrong secret: league's 401, nothing audited (client auth
        // never completed — the capability denys above are the
        // audited ones).
        $this->exchange($client, $subject, ['client_secret' => 'wrong-secret'])
            ->assertStatus(401)
            ->assertJsonPath('error', 'invalid_client');
    }

    public function test_private_key_jwt_authenticates_the_exchange(): void
    {
        $user = User::factory()->create();
        $subject = $this->subjectFor($user);
        $client = $this->exchangeClient('Assertion BFF');
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        // The assertion replaces the shared secret — no client_secret
        // rides along (never a downgrade path).
        $this->postJson('/oauth/token', [
            'grant_type' => self::MINT,
            'client_id' => (string) $client->getKey(),
            'client_assertion' => $this->signAssertion($key, [
                'client_id' => (string) $client->getKey(),
            ]),
            'client_assertion_type' => ClientAssertionVerifier::ASSERTION_TYPE,
            'subject_token' => $subject,
            'subject_token_type' => self::ACCESS,
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('issued_token_type', self::ACCESS);
    }

    /**
     * @return array{private: string, public: string, kid: string}
     */
    private function generateKey(): array
    {
        $pair = RsaKeyGenerator::generate(2048);
        $this->assertNotNull($pair);

        $kid = Jwk::thumbprintFromPem($pair['public']);
        $this->assertNotNull($kid);

        return ['private' => $pair['private'], 'public' => $pair['public'], 'kid' => $kid];
    }

    /**
     * @param  array{private: string, public: string, kid: string}  $key
     * @param  array<string, mixed>  $opts
     */
    private function signAssertion(array $key, array $opts): string
    {
        $clientId = (string) $opts['client_id'];
        $aud = $opts['aud'] ?? rtrim((string) config('app.url'), '/').'/oauth/token';

        $token = (new JwtFacade())->issue(
            new RsaSha256(),
            InMemory::plainText($key['private']),
            function (Builder $builder) use ($key, $clientId, $aud): Builder {
                return $builder
                    ->issuedBy($clientId)
                    ->relatedTo($clientId)
                    ->permittedFor($aud)
                    ->identifiedBy((string) Str::uuid())
                    ->withHeader('kid', $key['kid']);
            },
        );

        return $token->toString();
    }

    /**
     * @param  array{private: string, public: string, kid: string}  $key
     */
    private function registerKey(Client $client, array $key): void
    {
        ClientAuthKey::query()->create([
            'client_id' => (string) $client->getKey(),
            'kid' => $key['kid'],
            'public_key' => $key['public'],
            'revoked_at' => null,
        ]);
    }
}
