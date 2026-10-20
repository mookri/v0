<?php

namespace Tests\Platform\Features;

use App\Platform\Features\FeatureFlags;
use Tests\TenantIntegrationTestCase;

/**
 * Feature-flag kernel service (doc 07 §10): evaluation precedence
 * (tenant override ?? plan bundle ?? default ?? fail-closed), the 60 s
 * cache bound with immediate invalidation on change, the audited
 * `platform.flags.changed` tenant-override path, the flags:* operator
 * surface, and the ModuleRegistry gate (doc 01 §2-5).
 *
 * Requirement: F3 — feature-flag convention (PAYMENTS_ENABLED,
 * NOTIFY_EMAIL_ENABLED seeded default-off).
 */
final class FeatureFlagsTest extends TenantIntegrationTestCase
{
    private function flags(): FeatureFlags
    {
        return $this->app->make(FeatureFlags::class);
    }

    public function test_roadmap_flags_are_seeded_default_off_and_unknown_flags_fail_closed(): void
    {
        $this->assertDatabaseHas('feature_flags', ['code' => 'payments.enabled', 'default' => false]);
        $this->assertDatabaseHas('feature_flags', ['code' => 'notify.email', 'default' => false]);

        $this->assertFalse($this->flags()->enabled('payments.enabled'));
        $this->assertFalse($this->flags()->enabled('notify.email'));

        // Undefined flag, no explicit default: fail closed (rule 1).
        $this->assertFalse($this->flags()->enabled('never.declared'));
    }

    public function test_seeded_default_row_drives_evaluation(): void
    {
        $this->assertDatabaseHas('feature_flags', ['code' => 'payments.enabled', 'default' => false]);

        $this->artisan('flags:set', ['code' => 'payments.enabled', 'enabled' => 'true'])
            ->assertExitCode(0);

        $this->assertTrue($this->flags()->enabled('payments.enabled'));
    }

    public function test_tenant_override_beats_plan_bundle_beats_default(): void
    {
        $tenant = $this->makeTenant();

        // Layer 3: global default (seeded off).
        $this->assertFalse($this->flags()->enabled('payments.enabled', (string) $tenant->getKey()));

        // Layer 2: plan bundle turns it on; default loses. (Plan + link
        // written straight to the table, so flush the evaluation caches —
        // this test is about precedence; the cache bound has its own test.)
        \Illuminate\Support\Facades\DB::connection('central')->table('plans')->insert([
            'code' => 'standard',
            'name' => 'Standard',
            'flag_bundle' => json_encode(['payments.enabled' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::connection('central')->table('tenants')
            ->where('id', $tenant->getKey())
            ->update(['plan_code' => 'standard']);
        \Illuminate\Support\Facades\Cache::flush();

        $this->assertTrue($this->flags()->enabled('payments.enabled', (string) $tenant->getKey()));

        // Layer 1: explicit tenant override beats the bundle (service path
        // — no flush, setOverride invalidates its own cache).
        $this->flags()->setOverride('payments.enabled', false, (string) $tenant->getKey());

        $this->assertFalse($this->flags()->enabled('payments.enabled', (string) $tenant->getKey()));

        // The override is tenant-scoped: another tenant on the same plan
        // still sees the bundle.
        $other = $this->makeTenant(['plan_code' => 'standard']);
        \Illuminate\Support\Facades\Cache::flush();

        $this->assertTrue($this->flags()->enabled('payments.enabled', (string) $other->getKey()));
    }

    public function test_ambient_tenant_context_is_used_when_no_tenant_argument(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $this->flags()->setOverride('payments.enabled', true, (string) $tenant->getKey());

        // No explicit tenant argument: the resolved ambient context wins.
        $this->assertTrue($this->flags()->enabled('payments.enabled'));

        $this->clearContext();

        // Outside a resolved scope: global default only.
        $this->assertFalse($this->flags()->enabled('payments.enabled'));
    }

    public function test_changes_take_effect_immediately_despite_the_cache(): void
    {
        $tenant = $this->makeTenant();

        // Prime both cache layers with the current (off) state.
        $this->assertFalse($this->flags()->enabled('payments.enabled', (string) $tenant->getKey()));

        // Immediate — the 60 s TTL is the bound, not the delay.
        $this->flags()->setOverride('payments.enabled', true, (string) $tenant->getKey());
        $this->assertTrue($this->flags()->enabled('payments.enabled', (string) $tenant->getKey()));

        $this->flags()->setOverride('payments.enabled', null, (string) $tenant->getKey());
        $this->assertFalse($this->flags()->enabled('payments.enabled', (string) $tenant->getKey()));

        $this->flags()->setDefault('payments.enabled', true);
        $this->assertTrue($this->flags()->enabled('payments.enabled', (string) $tenant->getKey()));
    }

    public function test_tenant_override_change_is_audited_into_the_tenant_chain(): void
    {
        $tenant = $this->makeTenant();

        // Ambient context must be the target tenant — the audit chain is
        // per tenant (K-025), the flags:* command resolves it the same way.
        $this->resolveContext($tenant);

        $this->flags()->setOverride('payments.enabled', true, (string) $tenant->getKey());
        $this->flags()->setOverride('payments.enabled', false, (string) $tenant->getKey());

        $this->assertAuditRecorded($tenant, 'platform.flags.changed');
    }

    public function test_flags_set_command_validates_and_scopes(): void
    {
        $tenant = $this->makeTenant();

        // Unknown code cannot mint a global default (seeded catalog only).
        $this->artisan('flags:set', ['code' => 'never.declared', 'enabled' => 'true'])
            ->assertExitCode(1);

        // Bad value rejected.
        $this->artisan('flags:set', ['code' => 'payments.enabled', 'enabled' => 'maybe'])
            ->assertExitCode(1);

        // Global set, tenant set (audited — the command resolves the scope), reset.
        $this->artisan('flags:set', ['code' => 'payments.enabled', 'enabled' => 'true'])
            ->assertExitCode(0);
        $this->assertTrue($this->flags()->enabled('payments.enabled'));

        $this->artisan('flags:set', [
            'code' => 'payments.enabled',
            'enabled' => 'false',
            '--tenant' => (string) $tenant->getKey(),
        ])->assertExitCode(0);
        $this->assertFalse($this->flags()->enabled('payments.enabled', (string) $tenant->getKey()));
        $this->assertAuditRecorded($tenant, 'platform.flags.changed');

        $this->artisan('flags:set', [
            'code' => 'payments.enabled',
            'enabled' => 'reset',
            '--tenant' => (string) $tenant->getKey(),
        ])->assertExitCode(0);
        // Override cleared, global default (true) now wins for that tenant.
        $this->assertTrue($this->flags()->enabled('payments.enabled', (string) $tenant->getKey()));

        // reset without --tenant is invalid.
        $this->artisan('flags:set', ['code' => 'payments.enabled', 'enabled' => 'reset'])
            ->assertExitCode(1);

        // Unknown tenant rejected.
        $this->artisan('flags:set', [
            'code' => 'payments.enabled',
            'enabled' => 'true',
            '--tenant' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        ])->assertExitCode(1);
    }

    public function test_flags_list_command_reports_catalog(): void
    {
        $this->artisan('flags:list')->assertExitCode(0);

        \Illuminate\Support\Facades\Artisan::call('flags:list', ['--json' => true]);
        $json = \Illuminate\Support\Facades\Artisan::output();

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $codes = array_column($decoded, 'code');
        $this->assertContains('payments.enabled', $codes);
        $this->assertContains('notify.email', $codes);
    }

    public function test_module_registry_gates_flagged_modules_through_the_service(): void
    {
        $tenant = $this->makeTenant();
        $registry = $this->registryForFixture('discovery');

        $alpha = $registry->find('alpha');
        $bravo = $registry->find('bravo');

        // Undefined module flag: default-safe branch stays enabled.
        $this->assertTrue($registry->enabled($alpha));

        // Seeded default off (direct insert, so flush the evaluation
        // caches): flagged module disabled globally...
        \Illuminate\Support\Facades\DB::connection('central')->table('feature_flags')->insert([
            'code' => 'module.alpha',
            'kind' => 'module',
            'default' => false,
            'description' => 'Fixture module gate',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\Cache::flush();

        $this->assertFalse($registry->enabled($alpha));

        // ...but a per-tenant override re-enables it for that tenant
        // (service path — setOverride invalidates its own cache).
        $this->flags()->setOverride('module.alpha', true, (string) $tenant->getKey());
        $this->assertTrue($registry->enabled($alpha, (string) $tenant->getKey()));
        $this->assertFalse($registry->enabled($alpha));

        // A module without a flag never consults the service.
        $this->assertTrue($registry->enabled($bravo));
    }
}
