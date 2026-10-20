<?php

namespace Tests\Platform\Authz;

use App\Platform\Authz\Models\SodRule;
use Tests\TenantIntegrationTestCase;

/**
 * SoD runtime enforcement (SEC-011 §7.3): a granted capability is denied
 * at decision time when a blocking conflict rule involving the exercised
 * permission trips — even though the grant exists (rules added after
 * assignment, cross-assignment combinations). Denials render 403
 * `sod_conflict` with the blocking rule codes.
 */
final class SodRuntimeTest extends TenantIntegrationTestCase
{
    public function test_granted_capability_denied_by_later_rule(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:manage', 'catalog:item:view']);
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($user);

        // Baseline: the grant allows the write.
        $authed()->postJson('/api/v1/catalog/items', [
            'sku' => 'BASE-1', 'name' => 'Baseline',
        ], $headers)->assertCreated();

        // A blocking rule arriving after the grant trips at decision time.
        SodRule::factory()->create([
            'tenant_id' => null,
            'code' => 'sod.catalog_split',
            'permission_set' => ['catalog:item:manage', 'catalog:item:view'],
            'severity' => SodRule::SEVERITY_BLOCK,
        ]);

        $authed()->postJson('/api/v1/catalog/items', [
            'sku' => 'BASE-2', 'name' => 'Blocked',
        ], $headers)->assertForbidden()
            ->assertJsonPath('error.code', 'sod_conflict')
            ->assertJsonPath('error.details.rules', ['sod.catalog_split'])
            ->assertJsonPath('error.details.capability', 'catalog:item:manage');

        $this->assertAuditRecorded($tenant, 'authz.denied');
    }

    public function test_warn_rules_and_unrelated_rules_allow(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:manage', 'catalog:item:view']);
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($user);

        // Warn severity: assignment time owned the warning; runtime allows.
        SodRule::factory()->create([
            'tenant_id' => null,
            'code' => 'sod.catalog_warn',
            'permission_set' => ['catalog:item:manage', 'catalog:item:view'],
            'severity' => SodRule::SEVERITY_WARN,
        ]);

        // Involves other permissions only: never consulted here.
        SodRule::factory()->create([
            'tenant_id' => null,
            'code' => 'sod.elsewhere',
            'permission_set' => ['payments:payout:approve', 'payments:payout:release'],
            'severity' => SodRule::SEVERITY_BLOCK,
        ]);

        $authed()->postJson('/api/v1/catalog/items', [
            'sku' => 'WARN-1', 'name' => 'Allowed',
        ], $headers)->assertCreated();
    }

    public function test_tenant_override_lifts_runtime_block(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:manage', 'catalog:item:view']);
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($user);

        SodRule::factory()->create([
            'tenant_id' => null,
            'code' => 'sod.catalog_split',
            'permission_set' => ['catalog:item:manage', 'catalog:item:view'],
            'severity' => SodRule::SEVERITY_BLOCK,
        ]);
        SodRule::factory()->forTenant($tid)->create([
            'code' => 'sod.catalog_split',
            'permission_set' => ['catalog:item:manage', 'catalog:item:view'],
            'severity' => SodRule::SEVERITY_WARN,
        ]);

        $authed()->postJson('/api/v1/catalog/items', [
            'sku' => 'OVERRIDE-1', 'name' => 'Allowed',
        ], $headers)->assertCreated();
    }
}
