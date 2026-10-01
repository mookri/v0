<?php

namespace Tests\Platform\Tenancy;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

/**
 * tenants:backfill-v2 (TEN-005 amendment C2): legacy code/state copy to
 * slug/status with dry-run, idempotency, and collision abort.
 */
final class TenantBackfillV2Test extends TenantIntegrationTestCase
{
    public function test_dry_run_changes_nothing(): void
    {
        $id = $this->legacyTenant();

        $this->assertSame(0, Artisan::call('tenants:backfill-v2', ['--dry-run' => true]));
        $this->assertStringContainsString(
            'DRY-RUN: would backfill 1 tenant(s)',
            Artisan::output()
        );

        $row = DB::connection('central')->table('tenants')->where('id', $id)->first();
        $this->assertNull($row->slug);
        $this->assertNull($row->status);
    }

    public function test_backfill_copies_and_is_idempotent(): void
    {
        $id = $this->legacyTenant();

        $this->assertSame(0, Artisan::call('tenants:backfill-v2'));
        $this->assertStringContainsString('Backfilled 1 tenant(s).', Artisan::output());

        $row = DB::connection('central')->table('tenants')->where('id', $id)->first();
        $this->assertSame('legacy-code', $row->slug);
        $this->assertSame('active', $row->status);

        $this->assertSame(0, Artisan::call('tenants:backfill-v2'));
        $this->assertStringContainsString('Backfilled 0 tenant(s).', Artisan::output());
    }

    public function test_partial_rows_fill_only_missing_columns(): void
    {
        $id = (string) \Illuminate\Support\Str::ulid();
        DB::connection('central')->table('tenants')->insert([
            'id' => $id,
            'code' => 'partial-code',
            'slug' => 'custom-slug',
            'name' => 'Partial',
            'db_mode' => 'shared',
            'state' => 'suspended',
            'status' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, Artisan::call('tenants:backfill-v2'));

        $row = DB::connection('central')->table('tenants')->where('id', $id)->first();
        $this->assertSame('custom-slug', $row->slug);
        $this->assertSame('suspended', $row->status);
    }

    public function test_slug_collision_aborts_loudly(): void
    {
        $a = (string) \Illuminate\Support\Str::ulid();
        $b = (string) \Illuminate\Support\Str::ulid();
        DB::connection('central')->table('tenants')->insert([
            'id' => $a,
            'code' => 'owner-code',
            'slug' => 'taken-slug',
            'name' => 'Owner',
            'db_mode' => 'shared',
            'state' => 'active',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('central')->table('tenants')->insert([
            'id' => $b,
            'code' => 'taken-slug',
            'slug' => null,
            'name' => 'Collision',
            'db_mode' => 'shared',
            'state' => 'active',
            'status' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(1, Artisan::call('tenants:backfill-v2'));
        $this->assertStringContainsString('slug collision', Artisan::output());

        // The colliding row was not touched (abort before any write).
        $this->assertNull(DB::connection('central')->table('tenants')->where('id', $b)->first()->slug);
        $this->assertNull(DB::connection('central')->table('tenants')->where('id', $b)->first()->status);
    }

    public function test_new_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(DB::connection('central')->getSchemaBuilder()->hasTable('plans'));
        $this->assertTrue(DB::connection('central')->getSchemaBuilder()->hasTable('organizations'));
        $this->assertTrue(DB::connection('central')->getSchemaBuilder()->hasTable('org_memberships'));
        $this->assertTrue(DB::connection('central')->getSchemaBuilder()->hasColumn('tenants', 'base_currency'));
        $this->assertTrue(DB::connection('central')->getSchemaBuilder()->hasColumn('tenants', 'db_config_ciphertext'));
    }

    private function legacyTenant(): string
    {
        $id = (string) \Illuminate\Support\Str::ulid();
        DB::connection('central')->table('tenants')->insert([
            'id' => $id,
            'code' => 'legacy-code',
            'slug' => null,
            'name' => 'Legacy',
            'db_mode' => 'shared',
            'state' => 'active',
            'status' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
