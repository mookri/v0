<?php

namespace Tests\Platform\Authz;

use App\Platform\Authz\Models\CapabilityMetadata;
use App\Platform\Authz\Models\Policy;
use App\Platform\Modules\ModuleRegistry;
use Tests\TenantIntegrationTestCase;

/**
 * platform:sync-permissions (SEC-020/SEC-021, T-0K01-01.5): materializes
 * every manifest-declared capability into structured metadata per tenant,
 * validates stored policy selectors, and fails closed — before any write —
 * on malformed capability codes (AC-T-0K01-01-1).
 */
final class SyncPermissionsCommandTest extends TenantIntegrationTestCase
{
    public function test_sync_materializes_manifest_capabilities_with_parse_columns(): void
    {
        $this->app->instance(ModuleRegistry::class, $this->registryForFixture('discovery'));
        $tenant = $this->makeTenant();

        $this->artisan('platform:sync-permissions')->assertExitCode(0);

        $tenantId = (string) $tenant->getKey();

        $this->assertDatabaseHas('capability_metadata', [
            'tenant_id' => $tenantId,
            'code' => 'alpha:widget:view',
            'module' => 'alpha',
            'resource' => 'widget',
            'action' => 'view',
            'sensitive' => 0,
        ], 'central');

        $this->assertDatabaseHas('capability_metadata', [
            'tenant_id' => $tenantId,
            'code' => 'alpha:widget:approve',
            'module' => 'alpha',
            'resource' => 'widget',
            'action' => 'approve',
            'sensitive' => 1,
        ], 'central');

        // Kernel manifest entries (config/platform.php) are materialized too.
        $this->assertDatabaseHas('capability_metadata', [
            'tenant_id' => $tenantId,
            'code' => 'platform:policy:configure',
            'module' => 'platform',
            'resource' => 'policy',
            'action' => 'configure',
            'sensitive' => 1,
        ], 'central');

        // T-0K01-02: the restricted explain capability is materialized
        // too (action vocabulary extended via the kernel `actions` key).
        $this->assertDatabaseHas('capability_metadata', [
            'tenant_id' => $tenantId,
            'code' => 'platform:authz:explain',
            'module' => 'platform',
            'resource' => 'authz',
            'action' => 'explain',
            'sensitive' => 1,
        ], 'central');
    }

    public function test_sync_materializes_per_tenant(): void
    {
        $this->app->instance(ModuleRegistry::class, $this->registryForFixture('discovery'));
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        $this->artisan('platform:sync-permissions', ['--tenant' => (string) $tenantA->getKey()])
            ->assertExitCode(0);

        $this->assertGreaterThan(
            0,
            CapabilityMetadata::withoutGlobalScopes()->where('tenant_id', $tenantA->getKey())->count(),
        );
        $this->assertSame(
            0,
            CapabilityMetadata::withoutGlobalScopes()->where('tenant_id', $tenantB->getKey())->count(),
            'The --tenant option must scope the sync to one tenant.',
        );
    }

    public function test_sync_fails_closed_on_malformed_capability_code(): void
    {
        $this->app->instance(ModuleRegistry::class, $this->registryForFixture('bad-capability'));
        $this->makeTenant();

        $this->artisan('platform:sync-permissions')
            ->expectsOutputToContain('capability_parse_failed')
            ->assertExitCode(1);

        $this->assertDatabaseCount('capability_metadata', 0, 'central');
    }

    public function test_sync_fails_closed_on_invalid_stored_policy_selector(): void
    {
        // A policy whose selector names a module that is not registered.
        Policy::create([
            'tenant_id' => null,
            'name' => 'ghost-selector',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'ghost', 'resource' => 'thing', 'action' => 'view'],
            'status' => 'active',
        ]);

        $this->app->instance(ModuleRegistry::class, $this->registryForFixture('discovery'));
        $this->makeTenant();

        $this->artisan('platform:sync-permissions')
            ->expectsOutputToContain('policy_selector_invalid')
            ->assertExitCode(1);

        $this->assertDatabaseCount('capability_metadata', 0, 'central');
    }

    public function test_dry_run_reports_diff_without_writing(): void
    {
        $this->app->instance(ModuleRegistry::class, $this->registryForFixture('discovery'));
        $this->makeTenant();

        $this->artisan('platform:sync-permissions', ['--dry-run' => true])
            ->expectsOutputToContain('alpha:widget:view')
            ->assertExitCode(0);

        $this->assertDatabaseCount('capability_metadata', 0, 'central');
    }

    public function test_sync_is_idempotent(): void
    {
        $this->app->instance(ModuleRegistry::class, $this->registryForFixture('discovery'));
        $tenant = $this->makeTenant();

        $this->artisan('platform:sync-permissions')->assertExitCode(0);

        $count = CapabilityMetadata::withoutGlobalScopes()->where('tenant_id', $tenant->getKey())->count();
        $version = (int) CapabilityMetadata::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->where('code', 'alpha:widget:view')
            ->value('version');

        $this->artisan('platform:sync-permissions')
            ->expectsOutputToContain('0 add(s), 0 update(s), 0 removal(s)')
            ->assertExitCode(0);

        $this->assertSame($count, CapabilityMetadata::withoutGlobalScopes()->where('tenant_id', $tenant->getKey())->count());
        $this->assertSame($version, (int) CapabilityMetadata::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->where('code', 'alpha:widget:view')
            ->value('version'));
    }

    public function test_sync_removes_capabilities_no_longer_declared(): void
    {
        $this->app->instance(ModuleRegistry::class, $this->registryForFixture('discovery'));
        $tenant = $this->makeTenant();

        $this->artisan('platform:sync-permissions')->assertExitCode(0);

        // Stale row from a manifest that no longer exists.
        CapabilityMetadata::create([
            'tenant_id' => (string) $tenant->getKey(),
            'code' => 'alpha:widget:detonate',
            'name' => 'Detonate widgets',
            'module' => 'alpha',
            'resource' => 'widget',
            'action' => 'detonate',
            'version' => 1,
            'sensitive' => false,
        ]);

        $this->artisan('platform:sync-permissions')
            ->expectsOutputToContain('alpha:widget:detonate')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('capability_metadata', [
            'tenant_id' => (string) $tenant->getKey(),
            'code' => 'alpha:widget:detonate',
        ], 'central');
    }
}
