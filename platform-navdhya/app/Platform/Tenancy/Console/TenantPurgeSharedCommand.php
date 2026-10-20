<?php

namespace App\Platform\Tenancy\Console;

use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantPromotion;
use Illuminate\Console\Command;

/**
 * `php artisan tenant:purge-shared --tenant= [--force]` (TEN-014): delete
 * the tenant's retained shared rows after the rollback window. Refuses
 * without --force; reports the deleted count for the audit trail.
 */
final class TenantPurgeSharedCommand extends Command
{
    protected $signature = 'tenant:purge-shared {--tenant= : Tenant id or slug} {--force : Skip confirmation}';

    protected $description = "Delete a dedicated tenant's retained shared rows";

    public function handle(TenantPromotion $promotion): int
    {
        $key = (string) $this->option('tenant');
        if ($key === '') {
            $this->error('--tenant is required');

            return self::FAILURE;
        }

        $tenant = Tenant::query()->where('id', $key)->orWhere('slug', $key)->orWhere('code', $key)->first();
        if ($tenant === null) {
            $this->error("unknown tenant {$key}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Delete ALL shared rows for tenant {$key}?")) {
            $this->line('aborted');

            return self::SUCCESS;
        }

        $deleted = $promotion->purgeShared($tenant);
        $this->info("purged {$deleted} shared row(s) for tenant {$key}");

        return self::SUCCESS;
    }
}
