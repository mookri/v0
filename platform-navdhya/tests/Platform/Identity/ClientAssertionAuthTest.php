<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\ClientAssertionVerifier;
use App\Platform\Identity\OAuth\ClientAuthKey;
use App\Platform\Identity\OAuth\Jwk;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Identity\OAuth\RsaKeyGenerator;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Laravel\Passport\Client;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HmacSha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RsaSha256;
use Lcobucci\JWT\Token\Builder;
use RuntimeException;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * RFC 7523 `private_key_jwt` client authentication (ADR-0020 D3): the
 * assertion replaces the shared secret at the token endpoint for every
 * client-authenticated grant, key scoping keeps one BFF's compromise
 * inside that BFF, and every failure mode is indistinguishable from
 * "no credential" — never a downgrade to the secret.
 */
final class ClientAssertionAuthTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    public function test_client_credentials_authenticates_with_private_key_jwt(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        $token = $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($key, [
            'client_id' => (string) $client->getKey(),
        ])))->assertOk()->json();

        $this->assertNotEmpty($token['access_token']);
        $this->assertSame('Bearer', $token['token_type']);
    }

    public function test_shared_secret_still_works_without_assertion(): void
    {
        $client = $this->serviceClient();

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'service-secret-1',
        ])->assertOk()->json();

        $this->assertNotEmpty($token['access_token']);
    }

    public function test_bad_assertion_never_downgrades_to_the_shared_secret(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey(); // never registered
        $assertion = $this->signAssertion($key, ['client_id' => (string) $client->getKey()]);

        // A valid secret rides along: an assertion, once presented, is
        // the ONLY accepted credential for that request.
        $this->postJson('/oauth/token', array_merge(
            $this->assertionBody($client, $assertion),
            ['client_secret' => 'service-secret-1'],
        ))->assertUnauthorized();
    }

    public function test_unknown_kid_is_rejected(): void
    {
        $client = $this->serviceClient();
        $registered = $this->generateKey();
        $this->registerKey($client, $registered);

        $unregistered = $this->generateKey(); // valid RSA, right claims, wrong kid
        $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($unregistered, [
            'client_id' => (string) $client->getKey(),
        ])))->assertUnauthorized();
    }

    public function test_cross_client_key_cannot_authenticate_for_another_client(): void
    {
        $victim = $this->serviceClient('Victim BFF');
        $attacker = $this->serviceClient('Attacker BFF');
        $stolen = $this->generateKey();
        $this->registerKey($victim, $stolen);

        // Stolen victim key, assertion minted FOR the attacker client:
        // the (client, kid) lookup is scoped per client, so the row
        // never resolves for anyone but the victim.
        $this->postJson('/oauth/token', $this->assertionBody($attacker, $this->signAssertion($stolen, [
            'client_id' => (string) $attacker->getKey(),
        ])))->assertUnauthorized();

        // Replay of the victim's own valid assertion under the
        // attacker's client_id: same scoping boundary.
        $victimAssertion = $this->signAssertion($stolen, [
            'client_id' => (string) $victim->getKey(),
        ]);
        $this->postJson('/oauth/token', $this->assertionBody($attacker, $victimAssertion))
            ->assertUnauthorized();

        // Blast radius is exactly the victim: its own key still works.
        $this->postJson('/oauth/token', $this->assertionBody($victim, $this->signAssertion($stolen, [
            'client_id' => (string) $victim->getKey(),
        ])))->assertOk();
    }

    public function test_assertion_subject_mismatch_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        // Right key, right kid, claims pointing at someone else.
        $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($key, [
            'client_id' => (string) $client->getKey(),
            'iss' => 'someone-else',
            'sub' => 'someone-else',
        ])))->assertUnauthorized();
    }

    public function test_replayed_assertion_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);
        $assertion = $this->signAssertion($key, ['client_id' => (string) $client->getKey()]);
        $body = $this->assertionBody($client, $assertion);

        $this->postJson('/oauth/token', $body)->assertOk();

        // Identical assertion, second presentation: jti single-use.
        $this->postJson('/oauth/token', $body)->assertUnauthorized();
    }

    public function test_expired_assertion_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        $now = time();
        $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($key, [
            'client_id' => (string) $client->getKey(),
            'iat' => $now - 420,
            'exp' => $now - 120,
        ])))->assertUnauthorized();
    }

    public function test_overlong_assertion_lifetime_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        // exp-iat (600s) exceeds federation.client_assertion_max_ttl
        // (300s) + leeway: a long-lived assertion would widen the
        // replay window beyond policy.
        $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($key, [
            'client_id' => (string) $client->getKey(),
            'exp' => time() + 600,
        ])))->assertUnauthorized();
    }

    public function test_missing_jti_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($key, [
            'client_id' => (string) $client->getKey(),
            'jti' => null, // omit: replay protection is mandatory, not optional
        ])))->assertUnauthorized();
    }

    public function test_wrong_audience_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($key, [
            'client_id' => (string) $client->getKey(),
            'aud' => 'https://other.example/oauth/token',
        ])))->assertUnauthorized();
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);
        $assertion = $this->signAssertion($key, ['client_id' => (string) $client->getKey()]);

        $segments = explode('.', $assertion);
        $signature = $segments[2];
        $segments[2] = substr($signature, 0, 10)
            .($signature[10] === 'A' ? 'B' : 'A')
            .substr($signature, 11);

        $this->postJson('/oauth/token', $this->assertionBody($client, implode('.', $segments)))
            ->assertUnauthorized();
    }

    public function test_algorithm_confusion_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);
        $clientId = (string) $client->getKey();
        $aud = $this->tokenAudience();

        // HS256 signed with the PUBLIC key material as HMAC secret —
        // the classic alg-confusion forgery. RS256 is pinned, so the
        // header alg mismatch fails at the signature check.
        $hmac = (new JwtFacade())->issue(
            new HmacSha256(),
            InMemory::plainText($key['public']),
            function (Builder $builder) use ($clientId, $aud, $key): Builder {
                return $builder
                    ->issuedBy($clientId)
                    ->relatedTo($clientId)
                    ->permittedFor($aud)
                    ->identifiedBy((string) \Illuminate\Support\Str::uuid())
                    ->withHeader('kid', $key['kid']);
            },
        );
        $this->postJson('/oauth/token', $this->assertionBody($client, $hmac->toString()))
            ->assertUnauthorized();

        // alg=none, hand-crafted (lcobucci 5 refuses to mint unsigned
        // tokens — the server must still refuse to read one).
        $unsigned = $this->base64Url(json_encode(['alg' => 'none', 'typ' => 'JWT', 'kid' => $key['kid']]))
            .'.'.$this->base64Url(json_encode([
                'iss' => $clientId,
                'sub' => $clientId,
                'aud' => $aud,
                'iat' => time(),
                'exp' => time() + 300,
                'jti' => (string) \Illuminate\Support\Str::uuid(),
            ])).'.';
        $this->postJson('/oauth/token', $this->assertionBody($client, $unsigned))
            ->assertUnauthorized();
    }

    public function test_revoked_client_key_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key, now());

        $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($key, [
            'client_id' => (string) $client->getKey(),
        ])))->assertUnauthorized();
    }

    public function test_wrong_assertion_type_is_rejected(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);
        $assertion = $this->signAssertion($key, ['client_id' => (string) $client->getKey()]);

        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_assertion' => $assertion,
            'client_assertion_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        ])->assertUnauthorized();
    }

    public function test_assertion_auth_fails_closed_when_the_replay_store_is_down(): void
    {
        $client = $this->serviceClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        $throwing = $this->createMock(CacheRepository::class);
        $throwing->method('add')->willThrowException(new RuntimeException('store down'));
        $this->app->instance(ClientAssertionVerifier::class, new ClientAssertionVerifier($throwing));

        // Replay protection is a security check: a store that cannot
        // answer means assertion auth is unavailable — never bypassed.
        $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($key, [
            'client_id' => (string) $client->getKey(),
        ])))->assertUnauthorized();

        // The shared-secret path is unaffected (its own cache usage is
        // scoped elsewhere; no assertion presented => verifier unused).
        $this->app->forgetInstance(ClientAssertionVerifier::class);
        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'service-secret-1',
        ])->assertOk();
    }

    public function test_authorization_code_exchange_authenticates_with_assertion(): void
    {
        $user = User::factory()->create();
        $client = $this->oauthWebClient();
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        [$client, $tokens] = $this->userTokensFor($user, $client, [
            'client_assertion' => $this->signAssertion($key, [
                'client_id' => (string) $client->getKey(),
            ]),
            'client_assertion_type' => ClientAssertionVerifier::ASSERTION_TYPE,
        ]);

        $this->assertNotEmpty($tokens['access_token']);
    }

    public function test_refresh_grant_authenticates_with_assertion(): void
    {
        [$client, $tokens] = $this->userTokensFor(User::factory()->create());
        $key = $this->generateKey();
        $this->registerKey($client, $key);

        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) $client->getKey(),
            'refresh_token' => $tokens['refresh_token'],
            'client_assertion' => $this->signAssertion($key, [
                'client_id' => (string) $client->getKey(),
            ]),
            'client_assertion_type' => ClientAssertionVerifier::ASSERTION_TYPE,
        ])->assertOk()->json();

        $this->assertNotEmpty($refreshed['access_token']);
    }

    public function test_key_administration_commands_are_audited_and_idempotent(): void
    {
        $client = $this->serviceClient();
        $clientId = (string) $client->getKey();
        $key = $this->generateKey();
        $publicFile = tempnam(sys_get_temp_dir(), 'nvkey');
        $this->assertNotFalse($publicFile);
        file_put_contents($publicFile, $key['public']);

        try {
            // Register: kid defaults to the RFC 7638 thumbprint.
            $this->artisan('platform:client:register-key', [
                'id' => $clientId,
                '--public-key' => $publicFile,
            ])->assertSuccessful();

            $row = ClientAuthKey::query()
                ->where('client_id', $clientId)
                ->where('kid', $key['kid'])
                ->first();
            $this->assertNotNull($row);
            $this->assertNull($row->revoked_at);

            $this->assertNotNull(AuthEvent::query()
                ->where('event_type', 'client_key.registered')
                ->where('decision', 'allow')
                ->where('actor_client_id', $clientId)
                ->where('signing_key_id', $key['kid'])
                ->first());

            // Duplicate kid: denied AND the denial is audited (D5).
            $this->artisan('platform:client:register-key', [
                'id' => $clientId,
                '--public-key' => $publicFile,
            ])->assertFailed();
            $this->assertNotNull(AuthEvent::query()
                ->where('event_type', 'client_key.registered')
                ->where('decision', 'deny')
                ->where('reason_code', 'kid_already_registered')
                ->first());

            // Revoke: row kept for forensics, audit recorded, and the
            // key stops authenticating immediately.
            $this->artisan('platform:client:revoke-key', [
                'id' => $clientId,
                'kid' => $key['kid'],
            ])->assertSuccessful();

            $this->assertNotNull($row->refresh()->revoked_at);
            $this->assertNotNull(AuthEvent::query()
                ->where('event_type', 'client_key.revoked')
                ->where('decision', 'allow')
                ->where('signing_key_id', $key['kid'])
                ->first());

            $this->postJson('/oauth/token', $this->assertionBody($client, $this->signAssertion($key, [
                'client_id' => $clientId,
            ])))->assertUnauthorized();

            $this->artisan('auth:verify-chain')->assertExitCode(0);
        } finally {
            @unlink($publicFile);
        }
    }

    public function test_register_key_rejects_a_non_key_file(): void
    {
        $client = $this->serviceClient();
        $garbage = tempnam(sys_get_temp_dir(), 'nvbad');
        $this->assertNotFalse($garbage);
        file_put_contents($garbage, 'not a pem');

        try {
            $this->artisan('platform:client:register-key', [
                'id' => (string) $client->getKey(),
                '--public-key' => $garbage,
            ])->assertFailed();

            $this->assertSame(0, ClientAuthKey::query()->count());
        } finally {
            @unlink($garbage);
        }
    }

    /**
     * Confidential service client profiled for client_credentials —
     * the machine-client shape BFFs use.
     */
    private function serviceClient(string $name = 'Service client'): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => $name,
            'secret' => 'service-secret-1',
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE,
            'allowed_grants' => ['client_credentials'],
        ]);

        return $client;
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

    private function registerKey(Client $client, array $key, $revokedAt = null): void
    {
        ClientAuthKey::query()->create([
            'client_id' => (string) $client->getKey(),
            'kid' => $key['kid'],
            'public_key' => $key['public'],
            'revoked_at' => $revokedAt,
        ]);
    }

    /**
     * @param  array<string, mixed>  $opts  iss/sub/aud/jti (null omits)/iat/exp/kid overrides
     */
    private function signAssertion(array $key, array $opts): string
    {
        $clientId = (string) $opts['client_id'];
        $aud = $opts['aud'] ?? $this->tokenAudience();
        $jti = array_key_exists('jti', $opts) ? $opts['jti'] : (string) \Illuminate\Support\Str::uuid();

        $token = (new JwtFacade())->issue(
            new RsaSha256(),
            InMemory::plainText($key['private']),
            function (Builder $builder) use ($key, $clientId, $aud, $jti, $opts): Builder {
                $builder = $builder
                    ->issuedBy($opts['iss'] ?? $clientId)
                    ->relatedTo($opts['sub'] ?? $clientId)
                    ->permittedFor($aud)
                    ->withHeader('kid', $opts['kid'] ?? $key['kid']);

                if (is_string($jti)) {
                    $builder = $builder->identifiedBy($jti);
                }

                if (isset($opts['iat'])) {
                    $builder = $builder->issuedAt(new DateTimeImmutable('@'.(int) $opts['iat']));
                }

                if (isset($opts['exp'])) {
                    $builder = $builder->expiresAt(new DateTimeImmutable('@'.(int) $opts['exp']));
                }

                return $builder;
            },
        );

        return $token->toString();
    }

    /**
     * @return array<string, mixed>
     */
    private function assertionBody(Client $client, string $assertion): array
    {
        return [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_assertion' => $assertion,
            'client_assertion_type' => ClientAssertionVerifier::ASSERTION_TYPE,
        ];
    }

    private function tokenAudience(): string
    {
        return rtrim((string) config('app.url'), '/').'/oauth/token';
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
