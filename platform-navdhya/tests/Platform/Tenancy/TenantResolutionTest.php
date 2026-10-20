<?php

namespace Tests\Platform\Tenancy;

use App\Models\User;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Tenant resolution fail-closed envelope codes (TEN-006, testing strategy
 * §5 item 3): missing header, unknown tenant, suspended tenant, and
 * missing/inactive membership each deny with a stable machine code.
 */
final class TenantResolutionTest extends TenantIntegrationTestCase
{
    public function test_tenant_scoped_request_without_header_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/policies');

        $this->assertEnvelopeCode($response, 400, 'tenant_header_missing');
    }

    public function test_unknown_tenant_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/policies', ['X-Tenant-Id' => (string) Str::ulid()]);

        $this->assertEnvelopeCode($response, 401, 'tenant_not_in_token');
    }

    public function test_suspended_tenant_is_rejected(): void
    {
        $tenant = Tenant::factory()->suspended()->create();
        $this->makeMembership($tenant);

        $this->actingAs(User::find($this->lastMembershipUserId()));

        $response = $this->getJson('/api/v1/policies', $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 403, 'tenant_suspended');
    }

    public function test_user_without_membership_is_rejected(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/policies', $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 403, 'membership_inactive');
    }

    public function test_suspended_membership_is_rejected(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant, null, ['state' => TenantMembership::STATE_SUSPENDED]);

        $this->actingAs(User::find($membership->user_id));

        $response = $this->getJson('/api/v1/policies', $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 403, 'membership_inactive');
    }

    public function test_unauthenticated_request_is_rejected_before_tenant_resolution(): void
    {
        $tenant = $this->makeTenant();

        $response = $this->getJson('/api/v1/policies', $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 401, 'unauthenticated');
    }

    public function test_authorized_request_resolves_and_serves(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:view']);

        $response = $this->getJson('/api/v1/policies', $this->tenantHeaders($tenant));

        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta' => ['request_id', 'page']]);
    }

    private function lastMembershipUserId(): int
    {
        return (int) TenantMembership::query()->latest('id')->firstOrFail()->user_id;
    }
}
