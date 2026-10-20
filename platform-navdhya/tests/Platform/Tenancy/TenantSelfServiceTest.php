<?php

namespace Tests\Platform\Tenancy;

use App\Models\User;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * GET /api/v1/platform/tenants (TEN-006 §4.3, platform-global): lists the
 * authenticated principal's own memberships with tenant summaries.
 * Exempt from tenant resolution; never leaks other users' memberships.
 */
final class TenantSelfServiceTest extends TenantIntegrationTestCase
{
    public function test_guests_are_denied(): void
    {
        $this->getJson('/api/v1/platform/tenants')->assertUnauthorized();
    }

    public function test_lists_own_memberships_with_tenant_summaries(): void
    {
        $tenant = $this->makeTenant(['slug' => 'acme', 'status' => 'active']);
        [$user] = $this->actingAsMembership($tenant);

        $response = $this->actingAs($user)->getJson('/api/v1/platform/tenants');

        $response->assertOk();
        $response->assertJsonPath('data.0.tenant.slug', 'acme');
        $response->assertJsonPath('data.0.tenant.status', 'active');
        $response->assertJsonPath('data.0.object', 'tenant_membership');
        $response->assertJsonStructure(['data', 'meta' => ['request_id', 'page']]);
    }

    public function test_never_leaks_other_users_memberships(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $other = User::factory()->create();
        $this->makeMembership($tenant, $other);

        $response = $this->actingAs($user)->getJson('/api/v1/platform/tenants?limit=100');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertCount(1, $ids);
    }

    public function test_empty_for_users_without_memberships(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/platform/tenants')->assertOk()->assertJsonPath('data', []);
    }
}
