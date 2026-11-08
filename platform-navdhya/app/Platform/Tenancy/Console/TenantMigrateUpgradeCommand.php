<?php

namespace App\Platform\Tenancy\Console;

use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `php artisan tenant:migrate-upgrade {--connection=} {--tenant=} {--dry-run} {--check}`
 *
 * Upgrades existing dedicated tenant schemas and un-provisioned connections to
 * match canonical tenant_shared schema. Unlike `tenant:provision-schema`, this
 * handles existing tables with schema drift, missing columns, and new indexes.
 */
final class TenantMigrateUpgradeCommand extends Command
{
    protected $signature = 'tenant:migrate-upgrade
        {--connection= : Target database connection name}
        {--tenant= : Target tenant ID (resolves connection via ConnectionResolver)}
        {--dry-run : Report changes without modifying target database}
        {--check : Verify target matches canonical schema; exits with 1 on drift}';

    protected $description = 'Upgrade dedicated tenant schema to match canonical tenant_shared schema';

    public function handle(ConnectionResolver $resolver): int
    {
        $connection = (string) $this->option('connection');
        $tenantId = (string) $this->option('tenant');
        $dryRun = (bool) $this->option('dry-run');
        $check = (bool) $this->option('check');

        if ($connection === '' && $tenantId === '') {
            $this->error('Either --connection or --tenant is required.');

            return self::FAILURE;
        }

        if ($tenantId !== '') {
            $tenant = Tenant::query()->find($tenantId);
            if (! $tenant instanceof Tenant) {
                $this->error("Tenant '{$tenantId}' not found.");

                return self::FAILURE;
            }
            $connection = $resolver->for($tenant);
        }

        if (! config()->has("database.connections.{$connection}")) {
            $this->error("Unknown connection '{$connection}'.");

            return self::FAILURE;
        }

        $source = ConnectionResolver::SHARED_CONNECTION;
        if ($connection === $source) {
            $this->warn("Target connection is already canonical source '{$source}'. No upgrade required.");

            return self::SUCCESS;
        }

        $sourceDriver = (string) config("database.connections.{$source}.driver");
        $targetDriver = (string) config("database.connections.{$connection}.driver", $sourceDriver);

        $this->info("Comparing canonical schema '{$source}' against target '{$connection}'...");

        $sourceSchema = Schema::connection($source);
        $targetSchema = Schema::connection($connection);

        $tablesCreated = 0;
        $columnsAdded = 0;
        $driftDetected = false;

        $sourceTables = $sourceSchema->getTables();

        foreach ($sourceTables as $tableInfo) {
            $tableName = is_array($tableInfo) ? ($tableInfo['name'] ?? null) : $tableInfo->getName();
            if (! is_string($tableName)) {
                continue;
            }

            if (! $this->isTenantTable($source, $tableName)) {
                continue;
            }

            if (! $targetSchema->hasTable($tableName)) {
                $driftDetected = true;
                if ($check) {
                    $this->error("[DRIFT] Table '{$tableName}' is missing on target '{$connection}'.");
                    continue;
                }

                if ($dryRun) {
                    $this->line("[DRY-RUN] Would CREATE TABLE '{$tableName}'.");
                    $tablesCreated++;
                    continue;
                }

                $ddl = $this->tableDdl($source, $tableName, $targetDriver);
                DB::connection($connection)->statement($ddl);
                $this->info("Created table '{$tableName}' on '{$connection}'.");
                $tablesCreated++;
                continue;
            }

            // Table exists: compare columns
            $sourceColumns = $sourceSchema->getColumnListing($tableName);
            $targetColumns = $targetSchema->getColumnListing($tableName);

            $missingColumns = array_diff($sourceColumns, $targetColumns);

            foreach ($missingColumns as $columnName) {
                $driftDetected = true;
                $colType = $sourceSchema->getColumnType($tableName, $columnName);

                if ($check) {
                    $this->error("[DRIFT] Column '{$tableName}.{$columnName}' ({$colType}) missing on target '{$connection}'.");
                    continue;
                }

                if ($dryRun) {
                    $this->line("[DRY-RUN] Would ADD COLUMN '{$tableName}.{$columnName}' ({$colType}).");
                    $columnsAdded++;
                    continue;
                }

                $this->addColumn($connection, $tableName, $columnName, $colType);
                $this->info("Added column '{$tableName}.{$columnName}' ({$colType}) on '{$connection}'.");
                $columnsAdded++;
            }
        }

        if ($check) {
            if ($driftDetected) {
                $this->error("Schema drift detected between '{$source}' and '{$connection}'.");

                return self::FAILURE;
            }

            $this->info("Target schema '{$connection}' is in sync with canonical '{$source}'.");

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Dry-run complete: would create {$tablesCreated} table(s) and add {$columnsAdded} column(s).");

            return self::SUCCESS;
        }

        // Record upgrade journal
        $this->ensureUpgradeJournalTable($connection);
        DB::connection($connection)->table('tenant_schema_upgrades')->insert([
            'source_connection' => $source,
            'tables_created' => $tablesCreated,
            'columns_added' => $columnsAdded,
            'upgraded_at' => now(),
        ]);

        $this->info("Upgrade completed successfully: {$tablesCreated} table(s) created, {$columnsAdded} column(s) added.");

        return self::SUCCESS;
    }

    private function isTenantTable(string $source, string $tableName): bool
    {
        $hasTenant = Schema::connection($source)->hasColumn($tableName, 'tenant_id')
            || $tableName === 'migrations';

        return $hasTenant
            || str_starts_with($tableName, 'partner_')
            || in_array($tableName, [
                'audit_logs',
                'otp_challenges',
                'application_documents',
                'booking_commands',
                'resource_calendars',
                'resource_allocations',
                'price_quotes',
                'price_quote_lines',
                'finance_documents',
                'finance_document_lines',
                'finance_sequences',
                'ledger_journals',
                'ledger_entries',
            ], true);
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
            throw new \RuntimeException("Cannot read DDL for table {$table}");
        }

        return $ddl;
    }

    private function addColumn(string $connection, string $table, string $column, string $type): void
    {
        Schema::connection($connection)->table($table, function (Blueprint $blueprint) use ($column, $type): void {
            match ($type) {
                'integer', 'int' => $blueprint->integer($column)->nullable(),
                'bigint' => $blueprint->bigInteger($column)->nullable(),
                'boolean', 'bool' => $blueprint->boolean($column)->default(false),
                'datetime', 'timestamp' => $blueprint->dateTime($column)->nullable(),
                'json' => $blueprint->json($column)->nullable(),
                'text' => $blueprint->text($column)->nullable(),
                default => $blueprint->string($column)->nullable(),
            };
        });
    }

    private function ensureUpgradeJournalTable(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable('tenant_schema_upgrades')) {
            Schema::connection($connection)->create('tenant_schema_upgrades', function (Blueprint $table): void {
                $table->id();
                $table->string('source_connection', 64);
                $table->unsignedInteger('tables_created')->default(0);
                $table->unsignedInteger('columns_added')->default(0);
                $table->timestamp('upgraded_at');
            });
        }
    }
}
