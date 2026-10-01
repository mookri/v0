<?php

namespace App\Platform\Tenancy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `php artisan tenant:provision-schema --connection=` — run all loaded
 * module + kernel migrations on the named connection (dedicated tenant
 * databases, scratch targets, promotion destinations). The single
 * supported way to create tenant schema anywhere besides the default
 * migrate path; promotion refuses targets that skipped this step.
 */
final class TenantProvisionSchemaCommand extends Command
{
    protected $signature = 'tenant:provision-schema {--connection= : Target database connection (must exist in config)}';

    protected $description = 'Run all migrations on a tenant connection';

    public function handle(): int
    {
        $connection = (string) $this->option('connection');
        if ($connection === '') {
            $this->error('--connection is required');

            return self::FAILURE;
        }

        if (! config()->has("database.connections.{$connection}")) {
            $this->error("unknown connection {$connection}");

            return self::FAILURE;
        }

        // Clone tenant-table DDL from tenant_shared (migrate --database
        // cannot override the explicit connections module migrations
        // declare, so provisioning copies schema, not migration runs).
        $source = \App\Platform\Tenancy\ConnectionResolver::SHARED_CONNECTION;
        $driver = config("database.connections.{$connection}.driver", config("database.connections.{$source}.driver"));
        $cloned = 0;
        foreach (Schema::connection($source)->getTables() as $table) {
            $name = is_array($table) ? ($table['name'] ?? null) : $table->getName();
            if (! is_string($name)) {
                continue;
            }
            $hasTenant = Schema::connection($source)->hasColumn($name, 'tenant_id')
                || $name === 'migrations';
            if (! $hasTenant && ! str_starts_with($name, 'partner_') && $name !== 'audit_logs' && $name !== 'otp_challenges' && $name !== 'application_documents') {
                continue;
            }
            if (Schema::connection($connection)->hasTable($name)) {
                continue;
            }
            $ddl = $this->tableDdl($source, $name, (string) $driver);
            DB::connection($connection)->statement($ddl);
            $cloned++;
        }

        $this->info("provisioned {$cloned} table(s) on {$connection}");

        return self::SUCCESS;
    }

    private function tableDdl(string $source, string $table, string $driver): string
    {
        if ($driver === 'mysql') {
            $row = DB::connection($source)->selectOne("SHOW CREATE TABLE `{$table}`");
            $ddl = (array) $row;

            return (string) end($ddl);
        }

        $row = DB::connection($source)->selectOne(
            'SELECT sql FROM sqlite_master WHERE type = ? AND name = ?',
            ['table', $table]
        );
        $ddl = (string) ($row->sql ?? '');

        if ($ddl === '') {
            throw new \RuntimeException("cannot read DDL for table {$table}");
        }

        return $ddl;
    }
}
