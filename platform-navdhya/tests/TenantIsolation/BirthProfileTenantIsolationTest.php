<?php

namespace Tests\TenantIsolation;

use App\Modules\Jyotish\Models\BirthProfile;
use Tests\TenantIntegrationTestCase;

/**
 * Tenant Isolation Test for Jyotish Birth Profiles (T-JY01-02).
 * Verifies strict cross-tenant isolation: Tenant B receives 404 when querying Tenant A's profile.
 */
final class BirthProfileTenantIsolationTest extends TenantIntegrationTestCase
{
    public function test_tenant_cannot_read_another_tenants_birth_profile(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        [$userA] = $this->actingAsMembership($tenantA, ['jyotish:chart:read', 'jyotish:chart:write']);
        [$userB] = $this->actingAsMembership($tenantB, ['jyotish:chart:read', 'jyotish:chart:write']);

        $created = $this->actingAs($userA)->postJson('/api/v1/jyotish/birth-profiles', [
            'name' => 'Private Profile A',
            'birth_place' => 'New Delhi',
            'year' => 2026,
            'month' => 6,
            'day' => 15,
            'hour' => 8,
            'minute' => 0,
            'second' => 0,
        ], $this->tenantHeaders($tenantA))->assertCreated();

        $profileId = $created->json('data.id');
        $this->assertNotEmpty($profileId);

        // Tenant A can read its own profile
        $this->actingAs($userA)
            ->getJson("/api/v1/jyotish/birth-profiles/{$profileId}", $this->tenantHeaders($tenantA))
            ->assertOk();

        // Tenant B must receive 404 Not Found (fail-closed isolation)
        $this->actingAs($userB)
            ->getJson("/api/v1/jyotish/birth-profiles/{$profileId}", $this->tenantHeaders($tenantB))
            ->assertNotFound();
    }
}

