<?php

namespace App\Platform\Tenancy\Console;

use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantPromotion;
use Illuminate\Console\Command;

/**
 * `php artisan tenant:demote --tenant=` (TEN-014): reverse a cutover inside
 * the rollback window by flipping db_mode back to shared. Shared rows are
 * retained, so no copy is needed — the resolver routes back immediately.
 */
final class TenantDemoteCommand extends Command
{
    protected $signature = 'tenant:demote {--tenant= : Tenant id or slug}';

    protected $description = 'Demote a dedicated tenant back to shared (rollback window)';

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

        if ($tenant->db_mode !== 'dedicated') {
            $this->error("tenant {$key} is not dedicated");

            return self::FAILURE;
        }

        $promotion->demote($tenant);
        $this->info("demoted {$tenant->getKey()} back to shared");

        return self::SUCCESS;
    }
}
