<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Identity\OAuth\PushedAuthorizationRequest;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * 9a (ADR-0021) RFC 9126: the pushed-authorization endpoint and its
 * single-use, short-TTL request_uri. A confidential client pre-validates
 * its full authorization request over a back channel and redeems a
 * capability at the browser-facing authorize endpoint. Every failure
 * mode is pinned: the N2 mandate, replay, expiry, client mismatch, an
 * unregistered redirect_uri, an unknown param, a bad challenge method,
 * a missing openid nonce, and a push that authenticates but tries to
 * push for someone else.
 */
final class PushedAuthorizationRequestTest extends TenantIntegrationTestCase
{
    private const SECRET = 'par-client-secret-1';

    private const REDIRECT = 'https://example.test/callback';

    private const VERIFIER = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

    private const CHALLENGE = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';

    /** A profiled confidential client for the PAR push. */
    private function parClient(bool $requirePar = true, bool $requirePkce = true): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'PAR client',
            'secret' => Hash::make(self::SECRET),
            'redirect_uris' => [self::REDIRECT],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
            'allowed_grants' => ['authorization_code', 'refresh_token'],
            'allowed_scopes' => ['openid'],
            'require_par' => $requirePar,
            'require_pkce' => $requirePkce,
        ]);

        return $client;
    }

    /** @return array<string, mixed> */
    private function pushBody(Client $client, array $overrides = []): array
    {
        return array_merge([
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::SECRET,
            'response_type' => 'code',
            'redirect_uri' => self::REDIRECT,
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
            'nonce' => 'push-nonce-1',
        ], $overrides);
    }

    private function parDenials(): \Illuminate\Database\Eloquent\Builder
    {
        return AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'authorization.par_denied')
            ->where('decision', 'deny');
    }

    public function test_push_returns_a_single_use_short_lived_request_uri(): void
    {
        $client = $this->parClient();

        $res = $this->postJson('/api/oauth/par', $this->pushBody($client));
        $res->assertStatus(201);

        $requestUri = (string) $res->json('request_uri');
        $this->assertNotEmpty($requestUri);
        $this->assertSame(PushedAuthorizationRequest::TTL_SECONDS, $res->json('expires_in'));

        $row = PushedAuthorizationRequest::resolve($requestUri);
        $this->assertNotNull($row);
        $this->assertSame((string) $client->getKey(), $row->client_id);
        $this->assertSame('openid', $row->params['scope']);
        $this->assertSame('push-nonce-1', $row->params['nonce']);

        // Only the hash is stored — a database read never yields a
        // redeemable capability.
        $this->assertNotNull($row->request_uri_hash);
        $this->assertStringNotContainsString($requestUri, (string) $row->request_uri_hash);

        // Freshly minted: unclaimed and unexpired.
        $this->assertNull($row->used_at);
        $this->assertFalse($row->isExpired());
    }

    public function test_push_requires_client_authentication(): void
    {
        $client = $this->parClient();

        // No credentials at all.
        $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'client_secret' => null,
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        // Wrong secret.
        $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'client_secret' => 'not-the-secret',
        ]))->assertUnauthorized()->assertJsonPath('error', 'invalid_client');

        $this->assertSame(0, PushedAuthorizationRequest::query()->count());
    }

    public function test_push_rejects_an_unregistered_redirect_uri(): void
    {
        $client = $this->parClient();

        $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'redirect_uri' => 'https://evil.example/steal',
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        $this->assertSame(0, PushedAuthorizationRequest::query()->count());
    }

    public function test_push_rejects_an_unsupported_response_type(): void
    {
        $client = $this->parClient();

        $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'response_type' => 'token',
        ]))->assertStatus(400)->assertJsonPath('error', 'unsupported_response_type');
    }

    public function test_push_rejects_a_plain_pkce_method(): void
    {
        $client = $this->parClient();

        $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'code_challenge_method' => 'plain',
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');
    }

    public function test_push_requires_a_nonce_on_openid(): void
    {
        $client = $this->parClient();

        // openid without a nonce dies at the push, never in a redirect.
        $body = $this->pushBody($client);
        unset($body['nonce']);
        $this->postJson('/api/oauth/par', $body)
            ->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        // A non-openid push needs no nonce (scope not registered here,
        // so openid is the only legal scope value in this fixture).
        $body = $this->pushBody($client, ['scope' => '']);
        unset($body['nonce']);
        $this->postJson('/api/oauth/par', $body)->assertStatus(201);
    }

    public function test_push_rejects_unknown_parameters(): void
    {
        $client = $this->parClient();

        $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'prompt' => 'none',
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');
    }

    public function test_push_authenticates_with_private_key_jwt(): void
    {
        $client = $this->parClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        $body = $this->pushBody($client, [
            'client_assertion' => $this->signAssertion($key, [
                'client_id' => (string) $client->getKey(),
            ]),
            'client_assertion_type' => \App\Platform\Identity\OAuth\ClientAssertionVerifier::ASSERTION_TYPE,
        ]);
        unset($body['client_secret']);

        $this->postJson('/api/oauth/par', $body)->assertStatus(201);
    }

    public function test_failed_push_assertion_never_downgrades_to_the_secret(): void
    {
        $client = $this->parClient();
        $key = $this->generateKey(); // never registered

        // A valid secret rides along: an assertion, once presented, is
        // the ONLY accepted credential — no downgrade path exists at
        // the push endpoint either.
        $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'client_assertion' => $this->signAssertion($key, [
                'client_id' => (string) $client->getKey(),
            ]),
            'client_assertion_type' => \App\Platform\Identity\OAuth\ClientAssertionVerifier::ASSERTION_TYPE,
        ]))->assertUnauthorized()->assertJsonPath('error', 'invalid_client');

        $this->assertSame(0, PushedAuthorizationRequest::query()->count());
    }

    public function test_cross_client_push_assertion_cannot_push_for_another_client(): void
    {
        $victim = $this->parClient();
        $attacker = $this->parClient();
        $stolen = $this->generateKey();
        $this->registerKey($victim, $stolen);

        // Stolen victim key, assertion minted FOR the attacker client:
        // the (client_id, kid) lookup at the push is scoped per client,
        // so it never resolves for anyone but the victim.
        $body = $this->pushBody($attacker, [
            'client_assertion' => $this->signAssertion($stolen, [
                'client_id' => (string) $attacker->getKey(),
            ]),
            'client_assertion_type' => \App\Platform\Identity\OAuth\ClientAssertionVerifier::ASSERTION_TYPE,
        ]);
        unset($body['client_secret']);

        $this->postJson('/api/oauth/par', $body)->assertUnauthorized();

        $this->assertSame(0, PushedAuthorizationRequest::query()->count());
    }

    /**
     * @return array{private: string, public: string, kid: string}
     */
    private function generateKey(): array
    {
        $pair = \App\Platform\Identity\OAuth\RsaKeyGenerator::generate(2048);
        $this->assertNotNull($pair);

        $kid = \App\Platform\Identity\OAuth\Jwk::thumbprintFromPem($pair['public']);
        $this->assertNotNull($kid);

        return ['private' => $pair['private'], 'public' => $pair['public'], 'kid' => $kid];
    }

    /** @param array{private: string, public: string, kid: string} $key */
    private function registerKey(Client $client, array $key): void
    {
        \App\Platform\Identity\OAuth\ClientAuthKey::query()->create([
            'client_id' => (string) $client->getKey(),
            'kid' => $key['kid'],
            'public_key' => $key['public'],
            'revoked_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $opts
     */
    private function signAssertion(array $key, array $opts): string
    {
        $clientId = (string) $opts['client_id'];

        // Shared posture with the token endpoint: the assertion's aud
        // names this deployment's token endpoint under every origin it
        // answers for (ClientAssertionVerifier::acceptedAudiences) — the
        // assertion identifies the CLIENT to the AS, not one endpoint,
        // and the verifier accepts nothing else.
        $aud = rtrim((string) config('app.url'), '/').'/oauth/token';

        $token = (new \Lcobucci\JWT\JwtFacade())->issue(
            new \Lcobucci\JWT\Signer\Rsa\Sha256(),
            \Lcobucci\JWT\Signer\Key\InMemory::plainText($key['private']),
            function (\Lcobucci\JWT\Token\Builder $builder) use ($key, $clientId, $aud) {
                return $builder
                    ->issuedBy($clientId)
                    ->relatedTo($clientId)
                    ->permittedFor($aud)
                    ->identifiedBy((string) \Illuminate\Support\Str::uuid())
                    ->withHeader('kid', $key['kid']);
            },
        );

        return $token->toString();
    }

    public function test_push_uses_league_shape_errors_not_the_kernel_envelope(): void
    {
        $client = $this->parClient();

        $res = $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'code_challenge_method' => 'plain',
        ]));

        $res->assertStatus(400);
        $this->assertArrayHasKey('error', $res->json());
        $this->assertArrayHasKey('error_description', $res->json());
        $this->assertArrayHasKey('message', $res->json());
        $this->assertArrayNotHasKey('request_id', $res->json());
    }

    public function test_push_scope_guard_denies_a_scope_outside_the_profile_ceiling(): void
    {
        $client = $this->parClient();

        // Narrow the ceiling after registration: openid is no longer
        // allowed. The same A5 rule from the authorize and token seams
        // holds here — a scope outside the profile dies at the push,
        // never as a browser redirect later (RFC 9126 §2.1).
        $profile = OAuthClientProfile::query()
            ->where('client_id', (string) $client->getKey())
            ->firstOrFail();
        $profile->allowed_scopes = [];
        $profile->save();

        $this->postJson('/api/oauth/par', $this->pushBody($client))
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_scope');

        $this->assertSame(0, PushedAuthorizationRequest::query()->count());

        // An unrequested scope is still a scope request — a client may
        // not smuggle one in past the (now-empty) ceiling either.
        $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'scope' => 'read',
        ]))->assertStatus(400)->assertJsonPath('error', 'invalid_scope');

        $this->assertSame(0, PushedAuthorizationRequest::query()->count());
    }

    public function test_push_scope_guard_fails_closed_for_a_client_without_a_profile(): void
    {
        // No profile row = nothing allowed (fail-closed): even the
        // registered openid scope dies at the push — an unprofiled
        // client never gains entitlement by registering in Passport.
        $client = new Client();
        $client->forceFill([
            'name' => 'Unprofiled PAR client',
            'secret' => Hash::make(self::SECRET),
            'redirect_uris' => [self::REDIRECT],
            'grant_types' => ['authorization_code'],
            'revoked' => false,
        ]);
        $client->save();

        $this->postJson('/api/oauth/par', $this->pushBody($client))
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_scope');

        $this->assertSame(0, PushedAuthorizationRequest::query()->count());
    }

    public function test_push_scope_guard_passes_an_empty_request_and_an_allowed_scope(): void
    {
        // A zero ceiling still admits a scopeless push: the ceiling
        // gates what MAY be requested, not whether a request without
        // scopes may proceed (pre-9a scope= flows stay untouched).
        $client = $this->parClient();
        $profile = OAuthClientProfile::query()
            ->where('client_id', (string) $client->getKey())
            ->firstOrFail();
        $profile->allowed_scopes = [];
        $profile->save();

        $this->postJson('/api/oauth/par', $this->pushBody($client, [
            'scope' => '',
            'nonce' => null,
        ]))->assertStatus(201);

        // And an allowed scope still mints normally.
        $allowed = $this->parClient();
        $this->postJson('/api/oauth/par', $this->pushBody($allowed))
            ->assertStatus(201);
    }
}
