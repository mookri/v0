<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\Platform\Identity\Concerns\BuildsDpopProofs;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * A-SEC-004-6: session-bound access tokens — the openid ceremony's sid
 * is stamped onto the minted access token (JWT claim + row column from
 * one source: the entity stamp), session termination revokes the whole
 * lineage (access + refresh tokens) by that sid, and machine tokens
 * keep a NULL sid and outlive browser sessions. A-SEC-004-10 extends
 * the same contract across refresh rotation: the successor carries the
 * family's sid forward, so a rotated lineage dies with the session
 * exactly like an unrotated one (and sid-less families stay sid-less).
 */
final class AccessTokenSessionBindingTest extends TenantIntegrationTestCase
{
    use BuildsDpopProofs;
    use InteractsWithMsg91;
    use IssuesUserTokens;

    private const HOST = 'http://auth.localhost';

    private const SID = '01JSESSIONBOUNDTOKENS0000000001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();

        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
            'federation.mode' => 'federated',
            'identity.hmac_secret' => 'test-hmac-secret-for-federation-32-chars!!',
        ]);
    }

    public function test_openid_exchange_stamps_the_session_sid_on_the_token_claim_and_row(): void
    {
        $user = User::factory()->create();

        $this->seedCeremony(self::SID);

        [, $tokens] = $this->userTokensFor($user, $this->oidcClient(), [], [
            'scope' => 'openid',
            'nonce' => 'binding-nonce-1',
        ]);

        $claims = $this->decodePayload($tokens['access_token']);
        $this->assertSame(self::SID, $claims['sid']);

        $row = Passport::token()->newQuery()->whereKey((string) $claims['jti'])->first();
        $this->assertNotNull($row);
        $this->assertSame(self::SID, $row->sid);

        // The ID token keeps carrying the same sid — A-SEC-004-5
        // behavior is unchanged, the AT simply joined it.
        $idClaims = $this->decodePayload($tokens['id_token']);
        $this->assertSame(self::SID, $idClaims['sid']);
    }

    public function test_session_termination_revokes_the_lineage_by_sid(): void
    {
        $user = User::factory()->create();

        $this->seedCeremony(self::SID);

        [, $tokens] = $this->userTokensFor($user, $this->oidcClient(), [], [
            'scope' => 'openid',
            'nonce' => 'binding-nonce-2',
        ]);

        $claims = $this->decodePayload($tokens['access_token']);

        $row = Passport::token()->newQuery()->whereKey((string) $claims['jti'])->firstOrFail();
        $this->assertFalse((bool) $row->revoked);

        $refreshRow = Passport::refreshToken()->newQuery()
            ->where('access_token_id', $row->id)
            ->firstOrFail();
        $this->assertFalse((bool) $refreshRow->revoked);

        $this->post(self::HOST.'/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.signed_out', true);

        $row->refresh();
        $refreshRow->refresh();

        $this->assertTrue((bool) $row->revoked);
        $this->assertTrue((bool) $refreshRow->revoked);
    }

    public function test_machine_tokens_carry_no_sid_and_outlive_a_session_logout(): void
    {
        // A session-bound web token whose logout runs below.
        $webUser = User::factory()->create();
        $this->seedCeremony(self::SID);
        $this->userTokensFor($webUser, $this->oidcClient(), [], [
            'scope' => 'openid',
            'nonce' => 'binding-nonce-3',
        ]);

        $service = new Client();
        $service->forceFill([
            'name' => 'Machine session-binding client',
            'secret' => 'machine-secret-1',
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $service->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $service->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE,
            'allowed_grants' => ['client_credentials'],
            'allowed_scopes' => [],
        ]);

        $machine = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $service->getKey(),
            'client_secret' => 'machine-secret-1',
        ])->assertOk()->json();

        $machineClaims = $this->decodePayload($machine['access_token']);
        $this->assertArrayNotHasKey('sid', $machineClaims);

        $machineRow = Passport::token()->newQuery()
            ->whereKey((string) $machineClaims['jti'])
            ->firstOrFail();
        $this->assertNull($machineRow->sid);

        $this->post(self::HOST.'/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.signed_out', true);

        // The machine token is not part of any browser session's
        // lineage — the logout must not touch it.
        $machineRow->refresh();
        $this->assertFalse((bool) $machineRow->revoked);
    }

    public function test_rotation_carries_the_session_sid_onto_the_successor_tokens(): void
    {
        $user = User::factory()->create();

        $this->seedCeremony(self::SID);

        [$client, $tokens] = $this->userTokensFor($user, $this->oidcClient(), [], [
            'scope' => 'openid',
            'nonce' => 'binding-nonce-4',
        ]);

        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk()->json();

        $claims = $this->decodePayload($refreshed['access_token']);
        $this->assertSame(self::SID, $claims['sid'] ?? null);

        $row = Passport::token()->newQuery()->whereKey((string) $claims['jti'])->firstOrFail();
        $this->assertSame(self::SID, $row->sid);

        // The successor's own refresh token hangs off the successor —
        // the entire rotated family sits under the same lineage.
        $successorRefresh = Passport::refreshToken()->newQuery()
            ->where('access_token_id', $row->id)
            ->firstOrFail();
        $this->assertFalse((bool) $successorRefresh->revoked);
    }

    public function test_session_termination_kills_the_rotated_lineage(): void
    {
        $user = User::factory()->create();

        $this->seedCeremony(self::SID);

        [$client, $tokens] = $this->userTokensFor($user, $this->oidcClient(), [], [
            'scope' => 'openid',
            'nonce' => 'binding-nonce-5',
        ]);

        // Rotate FIRST — before A-SEC-004-10 the successor minted with
        // a NULL sid, so it sat outside the terminator's `where sid =`
        // net and survived the logout below with its refresh token.
        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk()->json();

        $successorClaims = $this->decodePayload($refreshed['access_token']);
        $successor = Passport::token()->newQuery()
            ->whereKey((string) $successorClaims['jti'])
            ->firstOrFail();
        $successorRefresh = Passport::refreshToken()->newQuery()
            ->where('access_token_id', $successor->id)
            ->firstOrFail();

        $this->post(self::HOST.'/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.signed_out', true);

        $successor->refresh();
        $successorRefresh->refresh();

        $this->assertTrue((bool) $successor->revoked, 'the rotated AT must die with the session');
        $this->assertTrue((bool) $successorRefresh->revoked, 'the rotated RT must die with the session');

        // And the kept successor refresh token mints nothing further:
        // reuse detection fails it closed (invalid_grant).
        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $refreshed['refresh_token'],
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    public function test_a_family_without_a_session_stays_sidless_across_rotation(): void
    {
        $user = User::factory()->create();

        // Non-openid exchange: no ceremony payload rides the code, so
        // this family never had a lineage — rotation must not invent
        // one (faithful carry-forward, not over-stamping).
        [$client, $tokens] = $this->userTokensFor($user, $this->oidcClient());

        $original = $this->decodePayload($tokens['access_token']);
        $this->assertArrayNotHasKey('sid', $original);

        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk()->json();

        $claims = $this->decodePayload($refreshed['access_token']);
        $this->assertArrayNotHasKey('sid', $claims);

        $row = Passport::token()->newQuery()->whereKey((string) $claims['jti'])->firstOrFail();
        $this->assertNull($row->sid);
    }

    public function test_rotation_keeps_both_the_session_sid_and_the_dpop_binding(): void
    {
        $user = User::factory()->create();
        $key = $this->dpopKey();

        $this->seedCeremony(self::SID);

        [$client, $tokens] = $this->userTokensFor($user, $this->oidcClient(), [], [
            'scope' => 'openid',
            'nonce' => 'binding-nonce-6',
        ], ['DPoP' => $this->dpopProof($key, 'POST', 'http://localhost/oauth/token')]);

        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
        ], ['DPoP' => $this->dpopProof($key, 'POST', 'http://localhost/oauth/token')])->assertOk()->json();

        // Both cross-slice stamps survive rotation from their one
        // source: entity stamp -> row column + JWT claim.
        $claims = $this->decodePayload($refreshed['access_token']);
        $jkt = $this->dpopThumbprint($key['jwk']);

        $this->assertSame(self::SID, $claims['sid'] ?? null);
        $this->assertSame($jkt, $claims['cnf']['dpop_jkt'] ?? null);

        $row = Passport::token()->newQuery()->whereKey((string) $claims['jti'])->firstOrFail();
        $this->assertSame(self::SID, $row->sid);
        $this->assertSame($jkt, $row->dpop_jkt);
    }

    private function oidcClient(): Client
    {
        return $this->oauthWebClient('Session-binding web client', [
            'allowed_scopes' => ['openid'],
        ]);
    }

    private function seedCeremony(string $sid): void
    {
        $this->withSession(['oidc.ceremony' => [
            'auth_time' => time(),
            'amr' => ['otp'],
            'acr' => 'aal1',
            'sid' => $sid,
        ]]);
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
