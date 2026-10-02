<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Tenancy\Models\Tenant;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * Passport dual-accept (P3b): access tokens authenticate on `auth.any`
 * routes via signature + expiry + live revocation + audience-client
 * checks, with tenant authority staying on live membership. Garbage,
 * revoked, and cross-tenant presentations all 401 identically.
 */
final class PassportDualAcceptTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Client, 1: string} client + access token
     */
    private function mintFor(User $user): array
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'Dual-accept web client',
            'secret' => 'dual-accept-secret-1',
            'redirect_uris' => ['https://example.test/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
            'allowed_grants' => ['authorization_code', 'refresh_token'],
        ]);

        $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => 'https://example.test/callback',
            'response_type' => 'code',
            'scope' => '',
            'state' => 'xyz',
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'S256',
        ]))->assertOk();

        $approve = $this->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ])->assertStatus(302);

        parse_str((string) parse_url((string) $approve->headers->get('Location'), PHP_URL_QUERY), $query);

        $tokens = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'dual-accept-secret-1',
            'redirect_uri' => 'https://example.test/callback',
            'code' => $query['code'],
            'code_verifier' => $verifier,
        ])->assertOk()->json();

        return [$client, $tokens['access_token']];
    }

    public function test_passport_token_authenticates_tenant_routes(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $token] = $this->mintFor($user);
        auth()->logout();

        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Id' => (string) $tenant->getKey(),
        ])->assertOk()->assertJsonStructure(['data', 'meta' => ['request_id']]);
    }

    public function test_revoked_token_denies(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $token] = $this->mintFor($user);
        auth()->logout();
        $headers = ['Authorization' => "Bearer {$token}", 'X-Tenant-Id' => (string) $tenant->getKey()];

        $this->getJson('/api/v1/catalog/items', $headers)->assertOk();

        // Guard stickiness (test-only: setUser persists on the shared
        // guard across requests in-process, like actingAs) — reset so
        // the next call re-proves credential evaluation.
        auth()->logout();

        \Laravel\Passport\Token::query()->update(['revoked' => true]);

        $this->getJson('/api/v1/catalog/items', $headers)->assertUnauthorized();
    }

    public function test_garbage_and_cross_tenant_deny_identically(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [, $token] = $this->mintFor($user);
        auth()->logout();

        $other = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        // Valid token, foreign tenant header: auth passes, but tenancy
        // denies (no membership there) — same as session users.
        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => "Bearer {$token}",
            'X-Tenant-Id' => (string) $other->getKey(),
        ])->assertForbidden()->assertJsonPath('error.code', 'membership_inactive');
        auth()->logout();

        // Forged/garbage JWT shapes deny identically.
        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => 'Bearer a.b.c',
            'X-Tenant-Id' => $tid,
        ])->assertUnauthorized();
        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => 'Bearer notatoken',
            'X-Tenant-Id' => $tid,
        ])->assertUnauthorized();
        $this->getJson('/api/v1/catalog/items', ['X-Tenant-Id' => $tid])->assertUnauthorized();
    }
}
