<?php

namespace Tests\Platform\Tenancy;

use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TenantIntegrationTestCase;

/**
 * Tenant schema upgrade tests (Phase 1, Task 1.1.1 & 1.1.2).
 */
final class TenantMigrateUpgradeTest extends TenantIntegrationTestCase
{
    protected function targetConnection(): string
    {
        $path = storage_path('framework/testing-upgrade-demo-'.uniqid().'.sqlite');
        touch($path);
        config()->set('database.connections.tenant_upgrade_demo', [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        return 'tenant_upgrade_demo';
    }

    public function test_check_reports_drift_on_empty_target(): void
    {
        $target = $this->targetConnection();

        $exit = Artisan::call('tenant:migrate-upgrade', [
            '--connection' => $target,
            '--check' => true,
        ]);

        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('Schema drift detected', $output);
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $target = $this->targetConnection();

        $exit = Artisan::call('tenant:migrate-upgrade', [
            '--connection' => $target,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('Dry-run complete: would create', $output);
        $this->assertFalse(Schema::connection($target)->hasTable('bookings'));
    }

    public function test_migrate_upgrade_creates_tables_and_adds_missing_columns(): void
    {
        $target = $this->targetConnection();

        // Step 1: Pre-create an older bookings table with missing columns to test upgrade
        Schema::connection($target)->create('bookings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->unsignedBigInteger('user_id');
            $table->string('status', 24)->default('DRAFT');
        });

        $this->assertTrue(Schema::connection($target)->hasTable('bookings'));
        $this->assertFalse(Schema::connection($target)->hasColumn('bookings', 'scheduled_at'));

        // Step 2: Run migrate-upgrade
        $exit = Artisan::call('tenant:migrate-upgrade', [
            '--connection' => $target,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exit, $output);
        $this->assertStringContainsString('Upgrade completed successfully', $output);

        // Verify column was added to existing table
        $this->assertTrue(Schema::connection($target)->hasColumn('bookings', 'scheduled_at'));
        $this->assertTrue(Schema::connection($target)->hasColumn('bookings', 'duration_minutes'));

        // Verify missing tables were created
        $this->assertTrue(Schema::connection($target)->hasTable('audit_logs'));

        // Step 3: Check returns 0 now
        $checkExit = Artisan::call('tenant:migrate-upgrade', [
            '--connection' => $target,
            '--check' => true,
        ]);
        $this->assertSame(0, $checkExit, Artisan::output());
    }
}
