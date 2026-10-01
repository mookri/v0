<?php

namespace Tests\Platform\Tenancy;

use App\Models\User;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

/**
 * tenant:adopt-legacy (pre-era doctrine): dry-run reports, backup refusal,
 * full adoption provisions tenant + owner membership and attributes every
 * NULL row, leaving zero NULLs behind.
 */
final class TenantAdoptLegacyTest extends TenantIntegrationTestCase
{
    private function seedNullRow(string $id): void
    {
        // Pre-era rows live in tables that predate the NOT NULL backfill;
        // the scratch table mirrors that contract (nullable tenant_id).
        if (! \Illuminate\Support\Facades\Schema::connection('tenant_shared')->hasTable('legacy_notes')) {
            \Illuminate\Support\Facades\Schema::connection('tenant_shared')->create('legacy_notes', function ($table): void {
                $table->string('id')->primary();
                $table->string('tenant_id')->nullable();
                $table->string('note')->nullable();
            });
        }
        DB::connection('tenant_shared')->table('legacy_notes')->insert([
            'id' => $id,
            'tenant_id' => null,
            'note' => 'pre-era',
        ]);
    }

    private function nullCount(): int
    {
        return DB::connection('tenant_shared')->table('legacy_notes')->whereNull('tenant_id')->count();
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $owner = User::factory()->create();
        $this->seedNullRow('01J0000000000000000000101');

        $this->assertSame(0, Artisan::call('tenant:adopt-legacy', [
            '--owner' => (string) $owner->id,
            '--dry-run' => true,
        ]));
        $this->assertStringContainsString('would be adopted', Artisan::output());
        $this->assertSame(0, Tenant::query()->where('slug', 'legacy')->count());
    }

    public function test_refuses_without_backup_confirmation(): void
    {
        $owner = User::factory()->create();

        $this->assertSame(1, Artisan::call('tenant:adopt-legacy', ['--owner' => (string) $owner->id]));
        $this->assertStringContainsString('--i-have-a-backup', Artisan::output());
    }

    public function test_full_adoption_provisions_and_attributes(): void
    {
        $owner = User::factory()->create();
        $this->seedNullRow('01J0000000000000000000102');
        $this->seedNullRow('01J0000000000000000000103');

        $this->assertSame(0, Artisan::call('tenant:adopt-legacy', [
            '--owner' => (string) $owner->id,
            '--i-have-a-backup' => true,
        ]));
        $this->assertStringContainsString('adopted tenant', Artisan::output());

        $tenant = Tenant::query()->where('slug', 'legacy')->firstOrFail();
        $this->assertSame('shared', $tenant->db_mode);

        $this->assertSame(0, $this->nullCount());
        $this->assertSame(
            2,
            DB::connection('tenant_shared')->table('legacy_notes')->where('tenant_id', (string) $tenant->getKey())->count()
        );

        $this->assertDatabaseHas('tenant_memberships', [
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $owner->id,
            'state' => 'active',
        ], 'central');
    }

    public function test_taken_slug_refuses(): void
    {
        $owner = User::factory()->create();
        $this->makeTenant(['slug' => 'legacy', 'code' => 'legacy-code']);

        $this->assertSame(1, Artisan::call('tenant:adopt-legacy', [
            '--owner' => (string) $owner->id,
            '--i-have-a-backup' => true,
        ]));
        $this->assertStringContainsString('already taken', Artisan::output());
    }
}
