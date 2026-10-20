<?php

namespace Tests\Platform\Authz;

use App\Platform\Authz\Events\SodConflictBlocked;
use App\Platform\Authz\Events\SodWarning;
use App\Platform\Authz\Models\Role;
use App\Platform\Authz\Models\RolePermission;
use App\Platform\Authz\Models\SodRule;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Support\Facades\Event;
use Tests\TenantIntegrationTestCase;

/**
 * Assignment-time SoD conflict enforcement (SEC-011 §7.2): blocking rules
 * reject grants with `sod_conflict`, warn rules proceed with an event,
 * self-grants hit `sod.role_grant_self`, tenant overrides win by code,
 * idempotent re-grants skip evaluation, every outcome is audit-logged.
 */
final class SodEnforcementTest extends TenantIntegrationTestCase
{
    private function makeRoleWithCaps(Tenant $tenant, TenantMembership $membership, string $code, array $caps): Role
    {
        $contextResolved = app(\App\Platform\Tenancy\TenantContext::class)->isResolved();
        if (! $contextResolved) {
            $this->resolveContext($tenant, $membership);
        }

        try {
            $role = Role::factory()->create(['code' => $code]);
            foreach ($caps as $capability) {
                RolePermission::create(['role_id' => $role->getKey(), 'capability_code' => $capability]);
            }
        } finally {
            if (! $contextResolved) {
                $this->clearContext();
            }
        }

        return $role;
    }

    private function platformRule(string $code, array $permissions, string $severity = SodRule::SEVERITY_BLOCK): SodRule
    {
        return SodRule::factory()->create([
            'tenant_id' => null,
            'code' => $code,
            'permission_set' => $permissions,
            'severity' => $severity,
        ]);
    }

    /**
     * @return array{0: Tenant, 1: \App\Models\User, 2: TenantMembership}
     */
    private function tenantWithIssuer(): array
    {
        $tenant = $this->makeTenant();
        // The issuer administers roles: these tests exercise what happens
        // AFTER authorization (SoD evaluation), so the issuer must clear
        // platform:role:* first.
        [$issuer, $issuerMembership] = $this->actingAsMembership($tenant, ['platform:role:view', 'platform:role:manage']);
        $this->stepUp($issuer);

        return [$tenant, $issuer, $issuerMembership];
    }

    public function test_conflicting_grant_blocked_with_rule_code(): void
    {
        Event::fake([SodConflictBlocked::class]);

        [$tenant, $issuer] = $this->tenantWithIssuer();
        $tid = (string) $tenant->getKey();

        $this->platformRule('sod.po_vendor_bill', ['purchasing:order:approve', 'invoicing:bill:approve']);

        $target = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $target, ['purchasing:order:approve']);
        $this->makeRoleWithCaps($tenant, $target, 'biller', ['invoicing:bill:approve']);

        $this->actingAs($issuer)->postJson(
            "/api/v1/platform/memberships/{$target->getKey()}/roles",
            ['tenant_id' => $tid, 'role_code' => 'biller']
        )->assertForbidden()
            ->assertJsonPath('error.code', 'sod_conflict')
            ->assertJsonPath('error.details.rules', ['sod.po_vendor_bill']);

        // Nothing persisted: the conflicting grant never happened (the
        // target keeps only its earlier, non-conflicting assignment).
        $billerId = (string) Role::query()->where('code', 'biller')->value('id');
        $this->assertDatabaseMissing('membership_role_assignments', [
            'membership_id' => (string) $target->getKey(),
            'role_id' => $billerId,
        ], 'central');

        Event::assertDispatched(SodConflictBlocked::class);
        $this->assertAuditRecorded($tenant, 'sod.evaluated');
    }

    public function test_warn_rule_proceeds_with_event(): void
    {
        Event::fake([SodWarning::class]);

        [$tenant, $issuer] = $this->tenantWithIssuer();
        $tid = (string) $tenant->getKey();

        $this->platformRule('sod.po_vendor_bill', ['purchasing:order:approve', 'invoicing:bill:approve'], SodRule::SEVERITY_WARN);

        $target = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $target, ['purchasing:order:approve']);
        $this->makeRoleWithCaps($tenant, $target, 'biller', ['invoicing:bill:approve']);

        $this->actingAs($issuer)->postJson(
            "/api/v1/platform/memberships/{$target->getKey()}/roles",
            ['tenant_id' => $tid, 'role_code' => 'biller']
        )->assertCreated();

        $this->assertDatabaseHas('membership_role_assignments', [
            'membership_id' => (string) $target->getKey(),
        ], 'central');
        Event::assertDispatched(SodWarning::class);
        $this->assertAuditRecorded($tenant, 'sod.evaluated');
    }

    public function test_self_grant_blocked(): void
    {
        [$tenant, $issuer, $issuerMembership] = $this->tenantWithIssuer();
        $tid = (string) $tenant->getKey();

        $this->platformRule('sod.role_grant_self', []);
        $this->makeRoleWithCaps($tenant, $issuerMembership, 'helper', []);

        $this->actingAs($issuer)->postJson(
            "/api/v1/platform/memberships/{$issuerMembership->getKey()}/roles",
            ['tenant_id' => $tid, 'role_code' => 'helper']
        )->assertForbidden()
            ->assertJsonPath('error.code', 'sod_conflict')
            ->assertJsonPath('error.details.rules', ['sod.role_grant_self']);
    }

    public function test_tenant_override_downgrade_wins(): void
    {
        [$tenant, $issuer] = $this->tenantWithIssuer();
        $tid = (string) $tenant->getKey();

        $this->platformRule('sod.po_vendor_bill', ['purchasing:order:approve', 'invoicing:bill:approve']);
        SodRule::factory()->forTenant($tid)->create([
            'code' => 'sod.po_vendor_bill',
            'permission_set' => ['purchasing:order:approve', 'invoicing:bill:approve'],
            'severity' => SodRule::SEVERITY_WARN,
        ]);

        $target = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $target, ['purchasing:order:approve']);
        $this->makeRoleWithCaps($tenant, $target, 'biller', ['invoicing:bill:approve']);

        $this->actingAs($issuer)->postJson(
            "/api/v1/platform/memberships/{$target->getKey()}/roles",
            ['tenant_id' => $tid, 'role_code' => 'biller']
        )->assertCreated();
    }

    public function test_idempotent_regrant_skips_evaluation(): void
    {
        [$tenant, $issuer] = $this->tenantWithIssuer();
        $tid = (string) $tenant->getKey();

        $target = $this->makeMembership($tenant);
        $this->makeRoleWithCaps($tenant, $target, 'biller', ['invoicing:bill:approve']);
        $this->makeRoleWithCaps($tenant, $target, 'refunder', ['invoicing:bill:refund']);
        $grantBiller = fn () => $this->actingAs($issuer)->postJson(
            "/api/v1/platform/memberships/{$target->getKey()}/roles",
            ['tenant_id' => $tid, 'role_code' => 'biller']
        );

        $grantBiller()->assertCreated();

        // Target now holds both halves of a conflict introduced later.
        $this->actingAs($issuer)->postJson(
            "/api/v1/platform/memberships/{$target->getKey()}/roles",
            ['tenant_id' => $tid, 'role_code' => 'refunder']
        )->assertCreated();
        $this->platformRule('sod.late_rule', ['invoicing:bill:approve', 'invoicing:bill:refund']);

        // A rule arriving after assignment must not break the idempotent
        // re-grant (no state change); runtime enforcement owns that case.
        $grantBiller()->assertCreated();
    }

    public function test_clean_grant_audits_evaluation(): void
    {
        [$tenant, $issuer] = $this->tenantWithIssuer();
        $tid = (string) $tenant->getKey();

        $target = $this->makeMembership($tenant);
        $this->makeRoleWithCaps($tenant, $target, 'viewer', ['catalog:read']);

        $this->actingAs($issuer)->postJson(
            "/api/v1/platform/memberships/{$target->getKey()}/roles",
            ['tenant_id' => $tid, 'role_code' => 'viewer']
        )->assertCreated();

        $this->assertAuditRecorded($tenant, 'sod.evaluated');
    }
}
