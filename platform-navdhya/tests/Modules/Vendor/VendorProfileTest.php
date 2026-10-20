<?php

namespace Tests\Modules\Vendor;

use App\Models\User;
use Database\Factories\PartnerApplicationFactory;
use Tests\TenantIntegrationTestCase;

/**
 * Vendor profile vertical (P12b): the APPROVED partner-vendor
 * application gate on create (409 state_forbidden / conflict), ownership
 * on partner reads and writes (403 foreign same-tenant, 404
 * cross-tenant), ownership-scoped lists, and the admin verify/suspend
 * verbs behind capability + step-up.
 */
final class VendorProfileTest extends TenantIntegrationTestCase
{
    /**
     * @param  \App\Platform\Tenancy\Models\Tenant  $tenant
     */
    private function approvedApplication($tenant, User $user): string
    {
        $application = PartnerApplicationFactory::new()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'identity_id' => (string) $user->getAuthIdentifier(),
            'track' => 'partner-vendor',
            'track_version' => 'partner-vendor-v1',
            'status' => 'APPROVED',
        ]);

        return (string) $application->getKey();
    }

    public function test_create_without_application_is_forbidden(): void
    {
        $tenant = $this->makeTenant();
        [$applicant] = $this->actingAsMembership($tenant);

        $this->actingAs($applicant)->postJson('/api/v1/vendor/partner/vendors', [
            'name' => 'No App Store',
        ], $this->tenantHeaders($tenant))
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'state_forbidden');
    }

    public function test_create_with_unapproved_application_is_forbidden(): void
    {
        $tenant = $this->makeTenant();
        [$applicant] = $this->actingAsMembership($tenant);

        PartnerApplicationFactory::new()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'identity_id' => (string) $applicant->getAuthIdentifier(),
            'track' => 'partner-vendor',
            'track_version' => 'partner-vendor-v1',
            'status' => 'DRAFT',
        ]);

        $this->actingAs($applicant)->postJson('/api/v1/vendor/partner/vendors', [
            'name' => 'Draft App Store',
        ], $this->tenantHeaders($tenant))
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'state_forbidden');
    }

    public function test_create_with_approved_application_succeeds_once(): void
    {
        $tenant = $this->makeTenant();
        [$applicant] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);
        $applicationId = $this->approvedApplication($tenant, $applicant);

        $created = $this->actingAs($applicant)->postJson('/api/v1/vendor/partner/vendors', [
            'name' => 'Krishna Stores',
            'city' => 'Mathura',
            'state' => 'Uttar Pradesh',
        ], $headers);

        $created->assertCreated()
            ->assertJsonPath('data.status', 'ACTIVE')
            ->assertJsonPath('data.verification_status', 'PENDING')
            ->assertJsonPath('data.application_id', $applicationId)
            ->assertJsonPath('data.owner_user_id', (int) $applicant->getAuthIdentifier());

        $vendorId = (string) $created->json('data.id');
        $this->assertDatabaseHas('vendors', [
            'id' => $vendorId,
            'tenant_id' => (string) $tenant->getKey(),
            'application_id' => $applicationId,
            'name' => 'Krishna Stores',
        ], 'tenant_shared');

        // One vendor per approved application.
        $this->actingAs($applicant)->postJson('/api/v1/vendor/partner/vendors', [
            'name' => 'Second Store',
        ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');
    }

    public function test_partner_access_is_ownership_scoped(): void
    {
        $tenant = $this->makeTenant();
        [$owner] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);
        $this->approvedApplication($tenant, $owner);

        $vendorId = (string) $this->actingAs($owner)->postJson('/api/v1/vendor/partner/vendors', [
            'name' => 'Owner Store',
        ], $headers)->assertCreated()->json('data.id');

        // Owner can read.
        $this->actingAs($owner)->getJson('/api/v1/vendor/partner/vendors/'.$vendorId, $headers)
            ->assertOk()
            ->assertJsonPath('data.id', $vendorId);

        // Same-tenant stranger: 403 on read and write.
        [$stranger] = $this->actingAsMembership($tenant);
        $this->actingAs($stranger)->getJson('/api/v1/vendor/partner/vendors/'.$vendorId, $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_missing');
        $this->actingAs($stranger)->patchJson('/api/v1/vendor/partner/vendors/'.$vendorId, [
            'name' => 'Hijacked',
        ], $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_missing');

        // Cross-tenant: invisible (no existence leakage).
        $other = $this->makeTenant();
        [$outsider] = $this->actingAsMembership($other);
        $this->actingAs($outsider)->getJson('/api/v1/vendor/partner/vendors/'.$vendorId, $this->tenantHeaders($other))
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        // Lists are ownership-scoped.
        $this->actingAs($owner)->getJson('/api/v1/vendor/partner/vendors', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.id', $vendorId);
        $this->actingAs($stranger)->getJson('/api/v1/vendor/partner/vendors', $headers)
            ->assertOk()
            ->assertJsonPath('data', []);

        // Owner update works.
        $this->actingAs($owner)->patchJson('/api/v1/vendor/partner/vendors/'.$vendorId, [
            'name' => 'Owner Store v2',
            'city' => 'Vrindavan',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.name', 'Owner Store v2')
            ->assertJsonPath('data.city', 'Vrindavan');
    }

    public function test_admin_verify_and_suspend_are_stepup_gated(): void
    {
        $tenant = $this->makeTenant();
        [$owner] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);
        $this->approvedApplication($tenant, $owner);

        $vendorId = (string) $this->actingAs($owner)->postJson('/api/v1/vendor/partner/vendors', [
            'name' => 'Admin Target',
        ], $headers)->assertCreated()->json('data.id');

        $admin = User::factory()->create();
        $membership = $this->makeMembership($tenant, $admin);
        $this->grantCapabilities($tenant, $membership, ['vendor:profile:view', 'vendor:profile:manage']);

        // No fresh step-up → denied.
        $this->actingAs($admin)->postJson('/api/v1/vendor/admin/vendors/'.$vendorId.':verify', [], $headers)
            ->assertForbidden()
            ->assertJsonPath('error.code', 'step_up_required');

        $this->stepUp($admin);
        $this->actingAs($admin)->postJson('/api/v1/vendor/admin/vendors/'.$vendorId.':verify', [], $headers)
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'VERIFIED');

        // Already verified → 409 state_forbidden.
        $this->actingAs($admin)->postJson('/api/v1/vendor/admin/vendors/'.$vendorId.':verify', [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'state_forbidden');

        $this->actingAs($admin)->postJson('/api/v1/vendor/admin/vendors/'.$vendorId.':suspend', [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'SUSPENDED');

        $this->actingAs($admin)->postJson('/api/v1/vendor/admin/vendors/'.$vendorId.':suspend', [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'state_forbidden');

        // Admin list sees every vendor; plain members cannot.
        $this->actingAs($admin)->getJson('/api/v1/vendor/admin/vendors', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.id', $vendorId);

        $this->actingAs($owner)->getJson('/api/v1/vendor/admin/vendors', $headers)
            ->assertForbidden();
    }
}
