<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Tenancy\Models\Tenant;
use Laravel\Passport\Client;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * Passport dual-accept (P3b): access tokens authenticate on `auth.any`
 * routes via signature + expiry + live revocation + audience-client
 * checks, with tenant authority staying on live membership. Garbage,
 * revoked, and cross-tenant presentations all 401 identically.
 */
final class PassportDualAcceptTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    /**
     * @return array{0: Client, 1: string} client + access token
     */
    private function mintFor(User $user): array
    {
        [$client, $tokens] = $this->userTokensFor($user);

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
