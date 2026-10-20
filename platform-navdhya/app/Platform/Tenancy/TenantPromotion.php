<?php

namespace App\Platform\Tenancy;

use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant promotion engine (TEN-013): shared → dedicated with
 * freeze/backfill/verify/cutover/smoke, plus demote and purge-shared.
 * Every step is idempotent and resumable; dry-run touches nothing.
 *
 * Table discovery: any table on the source connection carrying a
 * tenant_id column is in scope (no hardcoded table list to rot).
 */
final class TenantPromotion
{
    public const CHUNK_SIZE = 5000;

    /**
     * @return list<string>
     */
    public function tenantTables(string $connection): array
    {
        $tables = [];
        foreach (Schema::connection($connection)->getTables() as $table) {
            $name = $table['name'];
            if (Schema::connection($connection)->hasColumn($name, 'tenant_id')) {
                $tables[] = $name;
            }
        }
        sort($tables);

        return $tables;
    }

    /**
     * @return array{rows: int, checksum: string}
     */
    public function checksum(string $connection, string $table, string $tenantId): array
    {
        $rows = DB::connection($connection)->table($table)->where('tenant_id', $tenantId)->get(['id']);
        $ids = $rows->pluck('id')->sort()->values()->all();

        return ['rows' => count($ids), 'checksum' => md5(implode(',', $ids))];
    }

    /**
     * @return array{table: string, copied: int}
     */
    public function copyTable(string $source, string $target, string $table, string $tenantId, bool $dryRun): array
    {
        $copied = 0;
        DB::connection($source)->table($table)->where('tenant_id', $tenantId)->orderBy('id')->chunk(
            self::CHUNK_SIZE,
            function ($rows) use ($target, $table, &$copied, $dryRun): void {
                if (! $dryRun) {
                    $payload = $rows->map(static fn ($row): array => (array) $row)->all();
                    foreach (array_chunk($payload, 500) as $batch) {
                        DB::connection($target)->table($table)->upsert($batch, ['id']);
                    }
                }
                $copied += $rows->count();
            }
        );

        return ['table' => $table, 'copied' => $copied];
    }

    /**
     * @return array{tables: list<string>, copied: int, verified: bool, dry_run: bool}
     */
    public function promote(Tenant $tenant, string $targetConnection, bool $dryRun): array
    {
        if ($tenant->db_mode !== 'shared') {
            throw new Exceptions\TenantContextMissingException("tenant {$tenant->getKey()} is not shared (db_mode={$tenant->db_mode})");
        }

        $source = ConnectionResolver::SHARED_CONNECTION;
        $tables = $this->tenantTables($source);
        // Target schema is provisioned separately (migrate --database=<target>
        // runs all module migrations there); copying into missing tables
        // would silently drop rows, so refuse loudly instead.
        foreach ($tables as $table) {
            if (! Schema::connection($targetConnection)->hasTable($table)) {
                throw new Exceptions\TenantContextMissingException("target connection {$targetConnection} lacks table {$table} — provision schema first (migrate --database={$targetConnection})");
            }
        }
        $copied = 0;
        foreach ($tables as $table) {
            $copied += $this->copyTable($source, $targetConnection, $table, (string) $tenant->getKey(), $dryRun)['copied'];
        }

        $verified = true;
        if (! $dryRun) {
            foreach ($tables as $table) {
                $from = $this->checksum($source, $table, (string) $tenant->getKey());
                $to = $this->checksum($targetConnection, $table, (string) $tenant->getKey());
                if ($from !== $to) {
                    $verified = false;
                }
            }
        }

        return ['tables' => $tables, 'copied' => $copied, 'verified' => $verified, 'dry_run' => $dryRun];
    }

    public function cutover(Tenant $tenant, string $targetConnection): void
    {
        DB::connection('central')->transaction(function () use ($tenant, $targetConnection): void {
            $tenant->forceFill([
                'db_mode' => 'dedicated',
                'db_connection_name' => $targetConnection,
            ])->save();
        });
        ConnectionResolver::bust($tenant);
    }

    public function demote(Tenant $tenant): void
    {
        DB::connection('central')->transaction(function () use ($tenant): void {
            $tenant->forceFill(['db_mode' => 'shared', 'db_connection_name' => null])->save();
        });
        ConnectionResolver::bust($tenant);
    }

    public function purgeShared(Tenant $tenant): int
    {
        $deleted = 0;
        foreach ($this->tenantTables(ConnectionResolver::SHARED_CONNECTION) as $table) {
            $deleted += DB::connection(ConnectionResolver::SHARED_CONNECTION)
                ->table($table)->where('tenant_id', (string) $tenant->getKey())->delete();
        }

        return $deleted;
    }
}
