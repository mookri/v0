<?php

namespace Tests\Platform\Authz;

use App\Platform\Authz\Events\SodRuleCreated;
use App\Platform\Authz\Events\SodRuleUpdated;
use App\Platform\Authz\Models\SodRule;
use Illuminate\Support\Facades\Event;
use Tests\TenantIntegrationTestCase;

/**
 * SoD rule administration (SEC-011 §7.1): tenant custom rules, platform
 * overrides by code (downgrade allowed), platform immutability, and
 * fail-closed block removal (operator consent has no issuer yet).
 */
final class SodRuleAdminTest extends TenantIntegrationTestCase
{
    public function test_guests_are_denied(): void
    {
        $this->getJson('/api/v1/platform/sod-rules?tenant_id=x')->assertUnauthorized();
    }

    public function test_crud_lifecycle_with_events_and_audit(): void
    {
        Event::fake([SodRuleCreated::class, SodRuleUpdated::class]);

        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();
        $authed = fn () => $this->actingAs($issuer);

        $authed()->getJson('/api/v1/platform/roles')->assertStatus(422);

        // Empty sets are rejected (a rule that can never trigger).
        $authed()->postJson('/api/v1/platform/sod-rules', [
            'tenant_id' => $tid, 'code' => 'sod.empty_rule', 'name' => 'Empty',
        ])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        $created = $authed()->postJson('/api/v1/platform/sod-rules', [
            'tenant_id' => $tid, 'code' => 'sod.custom_duo', 'name' => 'Custom duo',
            'permission_set' => ['catalog:item:manage', 'catalog:item:view'],
        ])->assertCreated()->assertJsonPath('data.severity', 'block');
        $id = $created->json('data.id');

        // Duplicate tenant code conflicts.
        $authed()->postJson('/api/v1/platform/sod-rules', [
            'tenant_id' => $tid, 'code' => 'sod.custom_duo', 'name' => 'Dupe',
            'permission_set' => ['a:b:c'],
        ])->assertStatus(409);

        $listed = $authed()->getJson("/api/v1/platform/sod-rules?tenant_id={$tid}")->assertOk();
        $row = collect($listed->json('data'))->firstWhere('code', 'sod.custom_duo');
        $this->assertNotNull($row);
        $this->assertSame('tenant', $row['source']);

        $authed()->patchJson("/api/v1/platform/sod-rules/{$id}", [
            'tenant_id' => $tid, 'severity' => 'warn',
        ])->assertOk()->assertJsonPath('data.severity', 'warn');

        $authed()->deleteJson("/api/v1/platform/sod-rules/{$id}?tenant_id={$tid}")->assertOk();
        $authed()->deleteJson("/api/v1/platform/sod-rules/{$id}?tenant_id={$tid}")->assertNotFound();

        Event::assertDispatched(SodRuleCreated::class);
        Event::assertDispatched(SodRuleUpdated::class);
        $this->assertAuditRecorded($tenant, 'sod.rule.created');
        $this->assertAuditRecorded($tenant, 'sod.rule.updated');
    }

    public function test_platform_rows_immutable_but_overridable(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();
        $authed = fn () => $this->actingAs($issuer);

        $platform = SodRule::factory()->create([
            'tenant_id' => null, 'code' => 'sod.po_vendor_bill',
            'permission_set' => ['purchasing:order:approve', 'invoicing:bill:approve'],
        ]);
        $pid = (string) $platform->getKey();

        // Direct mutation of platform rows is rejected with guidance.
        $authed()->patchJson("/api/v1/platform/sod-rules/{$pid}", [
            'tenant_id' => $tid, 'severity' => 'warn',
        ])->assertStatus(409);
        $authed()->deleteJson("/api/v1/platform/sod-rules/{$pid}?tenant_id={$tid}")
            ->assertStatus(409);

        // Override by code: downgrade to warn proceeds.
        $override = $authed()->postJson('/api/v1/platform/sod-rules', [
            'tenant_id' => $tid, 'code' => 'sod.po_vendor_bill', 'name' => 'Tenant softer',
            'permission_set' => ['purchasing:order:approve', 'invoicing:bill:approve'],
            'severity' => 'warn',
        ])->assertCreated()->json('data.id');

        // Removing (disabling) a platform block fails closed: consent has
        // no issuer yet, so the request cannot proceed.
        $authed()->patchJson("/api/v1/platform/sod-rules/{$override}", [
            'tenant_id' => $tid, 'status' => 'disabled',
        ])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        $authed()->postJson('/api/v1/platform/sod-rules', [
            'tenant_id' => $tid, 'code' => 'sod.po_vendor_bill', 'name' => 'Dupe',
            'permission_set' => ['x'],
        ])->assertStatus(409);
    }

    public function test_issuer_without_membership_is_denied(): void
    {
        $tenant = $this->makeTenant();
        $outsider = \App\Models\User::factory()->create();

        $this->actingAs($outsider)->postJson('/api/v1/platform/sod-rules', [
            'tenant_id' => (string) $tenant->getKey(),
            'code' => 'sod.x_y', 'name' => 'X',
            'permission_set' => ['a:b:c'],
        ])->assertForbidden()->assertJsonPath('error.code', 'membership_inactive');
    }
}
