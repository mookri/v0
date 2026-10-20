<?php

namespace Tests\Platform\Authz;

use Tests\TenantIntegrationTestCase;

/**
 * Tiered machine throttling (REDIS-001 interim): per-key per-minute
 * ceilings from the key's rate_tier through the driver-agnostic limiter.
 * Humans pass through untouched; quota burns only on requests that pass
 * auth + scope.
 */
final class TierThrottleTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: string, 1: string} bearer secret + tenant id
     */
    private function keyWithTier(string $tenantId, string $issuerId, string $tier): array
    {
        $issuer = \App\Models\User::find($issuerId);
        $this->stepUp($issuer);

        $secret = $this->actingAs($issuer)->postJson('/api/v1/platform/api-keys', [
            'tenant_id' => $tenantId, 'name' => 'tier-probe', 'scopes' => ['catalog:read'], 'rate_tier' => $tier,
        ])->assertCreated()->json('data.secret');

        auth()->logout();

        return [$secret, $tenantId];
    }

    public function test_standard_key_trips_at_sixty_per_minute(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant, ['platform:apikey:view', 'platform:apikey:manage']);
        [$secret, $tid] = $this->keyWithTier((string) $tenant->getKey(), (string) $issuer->getAuthIdentifier(), 'standard');
        $headers = ['Authorization' => "Bearer {$secret}", 'X-Tenant-Id' => $tid];

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/catalog/items', $headers)->assertOk();
        }

        $this->getJson('/api/v1/catalog/items', $headers)
            ->assertStatus(429)->assertJsonPath('error.code', 'rate_limited')
            ->assertHeader('Retry-After', '60')
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertHeader('X-RateLimit-Remaining', '0');
    }

    public function test_enterprise_key_survives_sixty_one_requests(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant, ['platform:apikey:view', 'platform:apikey:manage']);
        [$secret, $tid] = $this->keyWithTier((string) $tenant->getKey(), (string) $issuer->getAuthIdentifier(), 'enterprise');
        $headers = ['Authorization' => "Bearer {$secret}", 'X-Tenant-Id' => $tid];

        for ($i = 0; $i < 61; $i++) {
            $this->getJson('/api/v1/catalog/items', $headers)->assertOk();
        }
    }

    public function test_humans_have_no_machine_quota(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        for ($i = 0; $i < 61; $i++) {
            $this->actingAs($user)->getJson('/api/v1/catalog/items', $headers)->assertOk();
        }
    }
}
