<?php

namespace App\Platform\Console;

use App\Platform\Outbox\DeadLetterStore;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * `php artisan outbox:replay [--all-exhausted] [--id=] [--tenant=]`
 *
 * Replays dead-lettered outbox events (T-JYAUT-00e / D-16).
 * Resets attempts on the original event and clears quarantine status.
 */
final class OutboxReplayCommand extends Command
{
    protected $signature = 'outbox:replay
        {--id= : Specific dead-letter ID to replay}
        {--all-exhausted : Replay all currently quarantined dead letters}
        {--tenant= : Scope replay to a specific tenant ID}';

    protected $description = 'Replay dead-lettered outbox events back into the active dispatch pipeline';

    public function handle(DeadLetterStore $store, TenantContext $context): int
    {
        $id = $this->option('id');
        $all = (bool) $this->option('all-exhausted');
        $tenantOption = $this->option('tenant');
        $tenantOption = is_string($tenantOption) && $tenantOption !== '' ? $tenantOption : null;

        if (! $id && ! $all) {
            $this->error('Must provide either --id=<ulid> or --all-exhausted flag.');
            return self::FAILURE;
        }

        $tenants = Tenant::query()
            ->where('state', Tenant::STATE_ACTIVE)
            ->when($tenantOption !== null, fn ($query) => $query->where('id', $tenantOption))
            ->get();

        $totalReplayed = 0;

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $context->clear();
            $context->set(
                $tenantId,
                null,
                null,
                (new ConnectionResolver())->cachedFor($tenant),
                ['system' => 'outbox-replay'],
            );

            try {
                if ($id) {
                    if ($store->replay($id)) {
                        $this->info("Replayed dead letter {$id} on tenant {$tenantId}.");
                        $totalReplayed++;
                    }
                } elseif ($all) {
                    $replayed = $store->replayAll($tenantId);
                    $this->info("Replayed {$replayed} dead letters on tenant {$tenantId}.");
                    $totalReplayed += $replayed;
                }
            } finally {
                $context->clear();
            }
        }

        $this->info("Outbox replay complete: total replayed = {$totalReplayed}.");

        return self::SUCCESS;
    }
}

