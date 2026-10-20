<?php

namespace App\Platform\Tenancy\Console;

use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantPromotion;
use Illuminate\Console\Command;

/**
 * `php artisan tenant:promote --tenant= --target= [--dry-run]` (TEN-013):
 * freeze (state=promoting, writes rejected) → chunked backfill with
 * checksums → verify → cutover transaction → smoke. Dry-run copies and
 * verifies nothing; every other step is logged.
 */
final class TenantPromoteCommand extends Command
{
    protected $signature = 'tenant:promote
        {--tenant= : Tenant id or slug}
        {--target= : Target connection name (must exist)}
        {--dry-run : Report scope and checksums without copying}';

    protected $description = 'Promote a shared tenant toward a dedicated connection';

    public function handle(TenantPromotion $promotion): int
    {
        $key = (string) $this->option('tenant');
        $target = (string) $this->option('target');
        $dryRun = (bool) $this->option('dry-run');

        if ($key === '' || $target === '') {
            $this->error('--tenant and --target are required');

            return self::FAILURE;
        }

        $tenant = Tenant::query()->where('id', $key)->orWhere('slug', $key)->orWhere('code', $key)->first();
        if ($tenant === null) {
            $this->error("unknown tenant {$key}");

            return self::FAILURE;
        }

        if (! config()->has("database.connections.{$target}")) {
            $this->error("unknown target connection {$target}");

            return self::FAILURE;
        }

        $result = $promotion->promote($tenant, $target, $dryRun);
        $this->line('tables: '.implode(',', $result['tables'])." copied={$result['copied']} verified=".($result['verified'] ? 'yes' : 'no'));

        if ($dryRun) {
            $this->info('dry run complete — no rows copied, no cutover performed');

            return self::SUCCESS;
        }

        if (! $result['verified']) {
            $this->error('checksum mismatch — aborting before cutover; tenant untouched');

            return self::FAILURE;
        }

        $tenant->forceFill(['state' => Tenant::STATE_PROMOTING])->save();
        $promotion->cutover($tenant, $target);
        $this->info("cutover complete: {$tenant->getKey()} is now dedicated on {$target}");

        return self::SUCCESS;
    }
}
