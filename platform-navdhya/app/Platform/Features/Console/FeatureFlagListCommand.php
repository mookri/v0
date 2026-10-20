<?php

namespace App\Platform\Features\Console;

use App\Platform\Features\FeatureFlags;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan flags:list [--tenant=] [--json]` — operator view of the
 * flag catalog (doc 07 §10.1): every seeded code with kind, global
 * default and description; with `--tenant` also that tenant's explicit
 * override and the value that resolves today (override ?? plan bundle ??
 * default).
 */
final class FeatureFlagListCommand extends Command
{
    protected $signature = 'flags:list {--tenant= : Include this tenant override and resolved value} {--json : Emit machine-readable JSON}';

    protected $description = 'List feature flags (code, kind, default, tenant override, resolved)';

    public function handle(FeatureFlags $flags): int
    {
        $tenantId = $this->option('tenant') !== null ? (string) $this->option('tenant') : null;

        $overrides = [];

        if ($tenantId !== null) {
            $rows = DB::connection('central')->table('tenant_feature_flags')
                ->where('tenant_id', $tenantId)->get();
            foreach ($rows as $row) {
                $overrides[(string) $row->code] = (bool) $row->enabled;
            }
        }

        $rows = [];

        foreach ($flags->defaults() as $code => $meta) {
            $row = [
                'code' => $code,
                'kind' => $meta['kind'],
                'default' => $meta['default'],
                'description' => $meta['description'],
            ];

            if ($tenantId !== null) {
                $row['override'] = $overrides[$code] ?? null;
                $row['resolved'] = $flags->enabled($code, $tenantId);
            }

            $rows[] = $row;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if ($tenantId === null) {
            $this->table(
                ['Code', 'Kind', 'Default', 'Description'],
                array_map(static fn (array $row): array => [
                    $row['code'],
                    $row['kind'],
                    $row['default'] ? 'on' : 'off',
                    $row['description'] ?? '-',
                ], $rows),
            );
        } else {
            $this->table(
                ['Code', 'Kind', 'Default', 'Override', 'Resolved'],
                array_map(static fn (array $row): array => [
                    $row['code'],
                    $row['kind'],
                    $row['default'] ? 'on' : 'off',
                    $row['override'] === null ? '-' : ($row['override'] ? 'on' : 'off'),
                    $row['resolved'] ? 'on' : 'off',
                ], $rows),
            );
        }

        $this->newLine();
        $this->info(sprintf('%d flag(s)%s.', count($rows), $tenantId !== null ? ' for tenant '.$tenantId : ''));

        return self::SUCCESS;
    }
}
