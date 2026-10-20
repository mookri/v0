<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\Platform\Identity\Concerns\BuildsDpopProofs;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * A-SEC-004-9: sender-constrained access tokens (RFC 9449).
 *
 *  - bind: a valid proof at the exchange lands `cnf.dpop_jkt` in the
 *    JWT and `dpop_jkt` on the row from one stamp; no proof = the
 *    unchanged unbound path (gradual adoption),
 *  - mandate: require_dpop denies a proofless exchange (and denies a
 *    mandated client at machine grants outright — fail closed),
 *  - reject: htm/htu/iat/signature/replay failures answer league 400
 *    `invalid_dpop_proof` before the code is ever touched,
 *  - enforce: a bound token at a resource (userinfo via auth.any) is
 *    refused without a live proof from ITS key — 401 envelope +
 *    RFC 9449 WWW-Authenticate challenge — while unbound tokens flow,
 *  - refresh: a bound family re-proves with the original key or does
 *    not refresh at all; a denial leaves the family intact.
 */
final class DpopBindingTest extends TenantIntegrationTestCase
{
    use BuildsDpopProofs;
    use IssuesUserTokens;

    private const TOKEN_URI = 'http://localhost/oauth/token';

    private const USERINFO_URI = 'http://localhost/oauth/userinfo';

    private const CHALLENGE_HEADER = 'DPoP error="invalid_dpop_proof", algs="ES256, RS256"';

    // ---------------------------------------------------------------- bind

    public function test_exchange_binds_a_valid_proof_to_the_token_claim_and_row(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $tokens] = $this->userTokensFor($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ]);

        $claims = $this->decodePayload($tokens['access_token']);
        $jkt = $this->dpopThumbprint($key['jwk']);

        $this->assertSame($jkt, $claims['cnf']['dpop_jkt'] ?? null);

        $row = Passport::token()->newQuery()->whereKey((string) $claims['jti'])->first();
        $this->assertNotNull($row);
        $this->assertSame($jkt, $row->dpop_jkt);
    }

    public function test_exchange_without_a_proof_issues_an_unbound_token(): void
    {
        $user = User::factory()->create();

        [, $tokens] = $this->userTokensFor($user);

        $claims = $this->decodePayload($tokens['access_token']);
        $this->assertArrayNotHasKey('cnf', $claims);

        $row = Passport::token()->newQuery()->whereKey((string) $claims['jti'])->first();
        $this->assertNotNull($row);
        $this->assertNull($row->dpop_jkt);
    }

    public function test_binding_coexists_with_the_session_sid_claim(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();
        $client = $this->oauthWebClient('DPoP OIDC client', ['allowed_scopes' => ['openid']]);

        $this->withSession(['oidc.ceremony' => [
            'auth_time' => time(),
            'amr' => ['otp'],
            'acr' => 'aal1',
            'sid' => '01JDPOPBOUNDSESSION00000000001',
        ]]);

        [, $tokens] = $this->userTokensFor($user, $client, [], [
            'scope' => 'openid',
            'nonce' => 'dpop-binding-nonce-1',
        ], ['DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI)]);

        $claims = $this->decodePayload($tokens['access_token']);

        $this->assertSame('01JDPOPBOUNDSESSION00000000001', $claims['sid'] ?? null);
        $this->assertSame($this->dpopThumbprint($key['jwk']), $claims['cnf']['dpop_jkt'] ?? null);
    }

    // ------------------------------------------------------------- mandate

    public function test_mandated_client_exchange_without_a_proof_is_denied(): void
    {
        $user = User::factory()->create();
        $client = $this->oauthWebClient('Mandated DPoP client', ['require_dpop' => true]);

        [, $response] = $this->userTokensAttempt($user, $client);

        $response->assertStatus(400)->assertJsonPath('error', 'invalid_dpop_proof');
    }

    public function test_mandated_client_exchange_with_a_proof_binds(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();
        $client = $this->oauthWebClient('Mandated DPoP client', ['require_dpop' => true]);

        [, $tokens] = $this->userTokensFor($user, $client, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ]);

        $claims = $this->decodePayload($tokens['access_token']);
        $this->assertSame($this->dpopThumbprint($key['jwk']), $claims['cnf']['dpop_jkt'] ?? null);
    }

    public function test_mandated_client_is_denied_at_machine_grants(): void
    {
        $client = $this->machineClient('Mandated machine client', ['require_dpop' => true]);

        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ])->assertStatus(400)->assertJsonPath('error', 'unsupported_grant');
    }

    public function test_machine_grant_ignores_a_proof_and_stays_unbound(): void
    {
        $client = $this->machineClient('Unmandated machine client');
        $key = $this->dpopKey();

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
        ], ['DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI)]);

        $response->assertOk();
        $claims = $this->decodePayload($response->json('access_token'));
        $this->assertArrayNotHasKey('cnf', $claims);
    }

    // -------------------------------------------------------------- reject

    public function test_proof_with_a_mismatched_htm_is_denied(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $response] = $this->userTokensAttempt($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'GET', self::TOKEN_URI),
        ]);

        $response->assertStatus(400)->assertJsonPath('error', 'invalid_dpop_proof');
    }

    public function test_proof_with_a_mismatched_htu_is_denied(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $response] = $this->userTokensAttempt($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', 'http://evil.test/oauth/token'),
        ]);

        $response->assertStatus(400)->assertJsonPath('error', 'invalid_dpop_proof');
    }

    public function test_proof_with_a_bad_signature_is_denied(): void
    {
        $user = User::factory()->create();
        $signer = $this->dpopKey();
        $other = $this->dpopKey();

        // Signed by $signer while advertising $other's public key.
        [, $response] = $this->userTokensAttempt($user, null, [], [], [
            'DPoP' => $this->dpopProof($signer, 'POST', self::TOKEN_URI, [], null, $other['jwk']),
        ]);

        $response->assertStatus(400)->assertJsonPath('error', 'invalid_dpop_proof');
    }

    public function test_proof_with_a_stale_iat_is_denied(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $response] = $this->userTokensAttempt($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI, ['iat' => time() - 300]),
        ]);

        $response->assertStatus(400)->assertJsonPath('error', 'invalid_dpop_proof');
    }

    public function test_replayed_proof_is_denied_at_the_token_endpoint(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();
        $proof = $this->dpopProof($key, 'POST', self::TOKEN_URI);

        [$client] = $this->userTokensFor($user, null, [], [], ['DPoP' => $proof]);

        // Second presentation of the SAME proof: the jti is spent, and
        // DPoP validation runs before the code is decrypted — so the
        // replay answer is deterministic regardless of the body.
        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri' => 'https://example.test/callback',
            'code' => 'already-consumed-code',
            'code_verifier' => self::VERIFIER,
        ], ['DPoP' => $proof])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_dpop_proof');
    }

    // ------------------------------------------------------------- enforce

    public function test_userinfo_accepts_a_bound_token_with_a_live_proof(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $tokens] = $this->userTokensFor($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ]);
        $at = $tokens['access_token'];
        $this->forgetActingUser();

        $this->getJson(self::USERINFO_URI, [
            'Authorization' => 'Bearer '.$at,
            'DPoP' => $this->dpopProof($key, 'GET', self::USERINFO_URI, [], $at),
        ])->assertOk()->assertJsonStructure(['sub']);
    }

    public function test_userinfo_denies_a_bound_token_without_a_proof(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $tokens] = $this->userTokensFor($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ]);

        $this->forgetActingUser();

        $this->getJson(self::USERINFO_URI, [
            'Authorization' => 'Bearer '.$tokens['access_token'],
        ])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'invalid_dpop_proof')
            ->assertHeader('WWW-Authenticate', self::CHALLENGE_HEADER);
    }

    public function test_userinfo_denies_a_bound_token_proved_by_the_wrong_key(): void
    {
        $user = User::factory()->create();
        $bound = $this->dpopKey();
        $intruder = $this->dpopKey();

        [, $tokens] = $this->userTokensFor($user, null, [], [], [
            'DPoP' => $this->dpopProof($bound, 'POST', self::TOKEN_URI),
        ]);
        $at = $tokens['access_token'];
        $this->forgetActingUser();

        $this->getJson(self::USERINFO_URI, [
            'Authorization' => 'Bearer '.$at,
            'DPoP' => $this->dpopProof($intruder, 'GET', self::USERINFO_URI, [], $at),
        ])->assertStatus(401)->assertJsonPath('error.code', 'invalid_dpop_proof');
    }

    public function test_userinfo_denies_a_proof_bound_to_a_different_token(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $tokens] = $this->userTokensFor($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ]);
        $at = $tokens['access_token'];
        $this->forgetActingUser();

        // Valid signature, right endpoint — ath computed over NOT this token.
        $this->getJson(self::USERINFO_URI, [
            'Authorization' => 'Bearer '.$at,
            'DPoP' => $this->dpopProof($key, 'GET', self::USERINFO_URI, [], 'a-different-access-token'),
        ])->assertStatus(401)->assertJsonPath('error.code', 'invalid_dpop_proof');
    }

    public function test_userinfo_rejects_a_replayed_proof(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $tokens] = $this->userTokensFor($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ]);
        $at = $tokens['access_token'];
        $this->forgetActingUser();
        $proof = $this->dpopProof($key, 'GET', self::USERINFO_URI, [], $at);

        $this->getJson(self::USERINFO_URI, ['Authorization' => 'Bearer '.$at, 'DPoP' => $proof])
            ->assertOk();

        // The successful first pass pins the resolved user on the shared
        // guard (test-app reuse — production is one app per request);
        // drop it so the second request re-enters the bearer path.
        $this->forgetActingUser();

        $this->getJson(self::USERINFO_URI, ['Authorization' => 'Bearer '.$at, 'DPoP' => $proof])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'invalid_dpop_proof');
    }

    public function test_userinfo_accepts_an_unbound_token_without_a_proof(): void
    {
        $user = User::factory()->create();

        [, $tokens] = $this->userTokensFor($user);

        $this->getJson(self::USERINFO_URI, [
            'Authorization' => 'Bearer '.$tokens['access_token'],
        ])->assertOk()->assertJsonStructure(['sub']);
    }

    // ------------------------------------------------------------- refresh

    public function test_refresh_with_the_original_key_keeps_the_family_bound(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $tokens] = $this->userTokensFor($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ]);
        $jkt = $this->dpopThumbprint($key['jwk']);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientIdentifierForRefresh($tokens),
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
        ], ['DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI)]);

        $response->assertOk();
        $claims = $this->decodePayload($response->json('access_token'));
        $this->assertSame($jkt, $claims['cnf']['dpop_jkt'] ?? null);
    }

    public function test_refresh_with_a_different_key_is_denied_and_the_family_survives(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();
        $intruder = $this->dpopKey();

        [, $tokens] = $this->userTokensFor($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ]);
        $refreshBody = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientIdentifierForRefresh($tokens),
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
        ];

        // Wrong key: denied BEFORE any rotation write — no lineage burn.
        $this->postJson('/oauth/token', $refreshBody, [
            'DPoP' => $this->dpopProof($intruder, 'POST', self::TOKEN_URI),
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_dpop_proof');

        // The family still refreshes with the original key.
        $this->postJson('/oauth/token', $refreshBody, [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ])->assertOk();
    }

    public function test_refresh_without_a_proof_is_denied_for_a_bound_family(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        [, $tokens] = $this->userTokensFor($user, null, [], [], [
            'DPoP' => $this->dpopProof($key, 'POST', self::TOKEN_URI),
        ]);

        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientIdentifierForRefresh($tokens),
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_dpop_proof');
    }

    /**
     * A client whose PROFILE and CLIENT ROW both permit
     * client_credentials — league's supportsGrantType is a second gate
     * on the row's grant_types, so widening only the profile would deny
     * for the wrong reason (unauthorized_client instead of the mandate
     * branch under test).
     *
     * @param  array<string, mixed>  $profileOverrides
     */
    private function machineClient(string $name, array $profileOverrides = []): Client
    {
        $client = $this->oauthWebClient($name, array_merge([
            'allowed_grants' => ['authorization_code', 'refresh_token', 'client_credentials'],
        ], $profileOverrides));

        $client->forceFill([
            'grant_types' => ['authorization_code', 'refresh_token', 'client_credentials'],
        ])->save();

        return $client;
    }

    /**
     * actingAs pins the user on the shared guard instance for every
     * later request in the same test — the bearer path under test must
     * start from a clean guard, so drop it after minting.
     */
    private function forgetActingUser(): void
    {
        $this->app['auth']->forgetGuards();
    }

    /**
     * The exchange response does not echo client_id — the tests know
     * the ceremony used a fresh default client, so resolve it the same
     * way userTokensFor() does. Kept tiny so the refresh bodies stay
     * readable.
     *
     * @param  array{access_token: string, refresh_token: string}  $tokens
     */
    private function clientIdentifierForRefresh(array $tokens): string
    {
        $claims = $this->decodePayload($tokens['access_token']);

        return (string) ($claims['aud'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $jwt): array
    {
        $segments = explode('.', $jwt);
        $this->assertCount(3, $segments);

        $base64 = strtr($segments[1], '-_', '+/');
        $padded = $base64.str_repeat('=', (4 - strlen($base64) % 4) % 4);
        $claims = json_decode((string) base64_decode($padded, true), true);

        $this->assertIsArray($claims);

        return $claims;
    }
}
