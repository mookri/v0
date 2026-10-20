<?php

namespace App\Platform\Tenancy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan tenants:backfill-v2 [--dry-run]` — one-time data migration
 * for the TEN-005 amendment (C2): copies legacy `code`→`slug` and
 * `state`→`status` where the new columns are empty.
 *
 * Idempotent and auditable: reports examined/copied/skipped counts, writes
 * nothing under --dry-run, and aborts loudly on slug collisions instead of
 * overwriting.
 */
final class TenantBackfillV2Command extends Command
{
    protected $signature = 'tenants:backfill-v2
        {--dry-run : Report what would change without writing}';

    protected $description = 'Backfill slug/status from legacy code/state (TEN-005 amendment)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $examined = 0;
        $copied = 0;
        $skipped = 0;

        $rows = DB::connection('central')->table('tenants')
            ->select(['id', 'code', 'slug', 'state', 'status'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $examined++;
            $patch = [];
            if ($row->slug === null && $row->code !== null) {
                $patch['slug'] = $row->code;
            }
            if ($row->status === null && $row->state !== null) {
                $patch['status'] = $row->state;
            }
            if ($patch === []) {
                $skipped++;
                continue;
            }
            if (isset($patch['slug'])) {
                $collision = DB::connection('central')->table('tenants')
                    ->where('slug', $patch['slug'])
                    ->where('id', '!=', $row->id)
                    ->exists();
                if ($collision) {
                    $this->error("slug collision for tenant {$row->id}: {$patch['slug']} — aborting");
                    return self::FAILURE;
                }
            }
            if (! $dryRun) {
                DB::connection('central')->table('tenants')->where('id', $row->id)->update($patch);
            }
            $copied++;
            $this->line(($dryRun ? '[dry-run] would copy ' : 'copied ').$row->id.': '.implode(',', array_keys($patch)));
        }

        $this->info("examined={$examined} copied={$copied} skipped={$skipped} dry-run=".($dryRun ? 'yes' : 'no'));

        if ($dryRun) {
            $this->line("DRY-RUN: would backfill {$copied} tenant(s); run without --dry-run to apply.");
        } else {
            $this->line("Backfilled {$copied} tenant(s).");
        }

        return self::SUCCESS;
    }
}
