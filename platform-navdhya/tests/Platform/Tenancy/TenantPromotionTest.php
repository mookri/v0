<?php

namespace Tests\Platform\Tenancy;

use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

/**
 * tenant:promote/demote/purge-shared (TEN-013/014): dry-run copies
 * nothing, full promote copies + verifies + cuts over, demote flips back,
 * purge deletes retained shared rows. Target is a scratch sqlite
 * connection carrying the same tenant schema.
 */
final class TenantPromotionTest extends TenantIntegrationTestCase
{
    protected function targetConnection(): string
    {
        $path = storage_path('framework/testing-tenant-demo-'.uniqid().'.sqlite');
        touch($path);
        config()->set('database.connections.tenant_demo', [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        // Provision real schema (all module migrations) — no hand mirrors.
        $exit = Artisan::call('tenant:provision-schema', ['--connection' => 'tenant_demo']);
        $this->assertSame(0, $exit, Artisan::output());

        return 'tenant_demo';
    }

    private function seedAuditRow(string $tenantId, string $id): void
    {
        DB::connection('tenant_shared')->table('audit_logs')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'action' => 'test.write',
            'hash' => 'h',
            'occurred_at' => now(),
        ]);
    }

    public function test_dry_run_reports_scope_without_copying(): void
    {
        $tenant = $this->makeTenant();
        $this->seedAuditRow((string) $tenant->getKey(), '01J0000000000000000000001');
        $target = $this->targetConnection();

        $this->assertSame(0, Artisan::call('tenant:promote', [
            '--tenant' => (string) $tenant->getKey(),
            '--target' => $target,
            '--dry-run' => true,
        ]));
        $output = Artisan::output();
        $this->assertStringContainsString('copied=1', $output);
        $this->assertStringContainsString('dry run complete', $output);
        $this->assertSame('shared', $tenant->fresh()->db_mode);
        $this->assertSame(0, DB::connection($target)->table('audit_logs')->where('tenant_id', (string) $tenant->getKey())->count());
    }

    public function test_promote_demotes_and_purges_full_cycle(): void
    {
        $tenant = $this->makeTenant();
        $tenantId = (string) $tenant->getKey();
        $this->seedAuditRow($tenantId, '01J0000000000000000000002');
        $target = $this->targetConnection();

        $this->assertSame(0, Artisan::call('tenant:promote', ['--tenant' => $tenantId, '--target' => $target]));
        $this->assertStringContainsString('cutover complete', Artisan::output());
        $fresh = $tenant->fresh();
        $this->assertSame('dedicated', $fresh->db_mode);
        $this->assertSame($target, $fresh->db_connection_name);
        $this->assertSame(
            '01J0000000000000000000002',
            DB::connection($target)->table('audit_logs')->where('tenant_id', $tenantId)->value('id')
        );

        $this->assertSame(0, Artisan::call('tenant:demote', ['--tenant' => $tenantId]));
        $this->assertSame('shared', $tenant->fresh()->db_mode);

        $this->assertSame(0, Artisan::call('tenant:purge-shared', ['--tenant' => $tenantId, '--force' => true]));
        $this->assertSame(0, DB::connection('tenant_shared')->table('audit_logs')->where('tenant_id', $tenantId)->count());
    }

    public function test_promote_rejects_unknown_tenant_and_target(): void
    {
        $this->assertSame(1, Artisan::call('tenant:promote', ['--tenant' => 'ghost', '--target' => 'tenant_shared']));
        $this->assertStringContainsString('unknown tenant', Artisan::output());

        $tenant = $this->makeTenant();
        $this->assertSame(1, Artisan::call('tenant:promote', ['--tenant' => (string) $tenant->getKey(), '--target' => 'ghost-conn']));
        $this->assertStringContainsString('unknown target connection', Artisan::output());
    }

    public function test_target_mirror_matches_canonical_audit_schema(): void
    {
        $target = $this->targetConnection();
        $canonical = DB::connection('tenant_shared')->getSchemaBuilder()->getColumnListing('audit_logs');
        $mirror = DB::connection($target)->getSchemaBuilder()->getColumnListing('audit_logs');
        sort($canonical);
        sort($mirror);
        $this->assertSame($canonical, $mirror);
    }
}
