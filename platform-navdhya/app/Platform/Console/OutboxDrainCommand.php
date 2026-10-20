<?php

namespace App\Platform\Console;

use App\Platform\Outbox\OutboxEvent;
use App\Platform\Outbox\OutboxHandlers;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

/**
 * `php artisan outbox:drain [--limit=100] [--tenant=]`
 *
 * Dispatches pending outbox events (F2) for every ACTIVE tenant, oldest
 * first. Each event is claimed (row lock), handled and marked
 * dispatched_at inside ONE transaction: concurrent drains cannot
 * double-dispatch a row, and a handler failure rolls the claim back so
 * the row stays pending with attempts+1 and the error recorded.
 *
 * Kernel posture: the drain resolves each tenant's context itself
 * (clear-then-set, TEN-012) so every query rides that tenant's
 * connection and TenantScope — it never lifts a scope and never
 * dispatches across tenants. Rows at MAX_ATTEMPTS stay pending for ops
 * (reported, not re-claimed); unhandled event types stay pending without
 * burning attempts so consumers can register later.
 */
final class OutboxDrainCommand extends Command
{
    protected $signature = 'outbox:drain
        {--limit=100 : Maximum events to dispatch per tenant in this pass}
        {--tenant= : Scope the pass to one tenant id}';

    protected $description = 'Dispatch pending outbox events to registered handlers (exactly once per claim)';

    public function handle(OutboxHandlers $handlers, TenantContext $context): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $tenantOption = $this->option('tenant');
        $tenantOption = is_string($tenantOption) && $tenantOption !== '' ? $tenantOption : null;

        $registered = $handlers->types();
        if ($registered === []) {
            $this->info('No outbox handlers registered — nothing to drain.');
            return self::SUCCESS;
        }

        $tenants = Tenant::query()
            ->where('state', Tenant::STATE_ACTIVE)
            ->when($tenantOption !== null, fn ($query) => $query->where('id', $tenantOption))
            ->get();

        $total = ['dispatched' => 0, 'failed' => 0, 'exhausted' => 0, 'unhandled' => 0];

        foreach ($tenants as $tenant) {
            $context->clear();
            $context->set(
                (string) $tenant->getKey(),
                null,
                null,
                (new ConnectionResolver())->cachedFor($tenant),
                ['system' => 'outbox-drain'],
            );

            $counts = $this->drainTenant($handlers, $registered, $limit);

            foreach ($counts as $key => $value) {
                $total[$key] += $value;
            }

            $this->line(sprintf(
                'tenant=%s dispatched=%d failed=%d exhausted=%d unhandled=%d',
                (string) $tenant->getKey(),
                $counts['dispatched'],
                $counts['failed'],
                $counts['exhausted'],
                $counts['unhandled'],
            ));
        }

        $context->clear();

        $this->info(sprintf(
            'outbox:drain complete — dispatched=%d failed=%d exhausted=%d unhandled=%d',
            $total['dispatched'],
            $total['failed'],
            $total['exhausted'],
            $total['unhandled'],
        ));

        return self::SUCCESS;
    }

    /**
     * Claim-and-dispatch loop for one tenant (context already resolved).
     *
     * @param  list<string>  $registered
     * @return array{dispatched: int, failed: int, exhausted: int, unhandled: int}
     */
    private function drainTenant(OutboxHandlers $handlers, array $registered, int $limit): array
    {
        $counts = ['dispatched' => 0, 'failed' => 0, 'exhausted' => 0, 'unhandled' => 0];
        $failedThisPass = [];

        $counts['exhausted'] = OutboxEvent::query()
            ->whereNull('dispatched_at')
            ->where('attempts', '>=', OutboxEvent::MAX_ATTEMPTS)
            ->count();
        $counts['unhandled'] = OutboxEvent::query()
            ->whereNull('dispatched_at')
            ->where('attempts', '<', OutboxEvent::MAX_ATTEMPTS)
            ->whereNotIn('event_type', $registered)
            ->count();

        while (($counts['dispatched'] + $counts['failed']) < $limit) {
            // Failures from THIS pass are not retried until the next
            // scheduled pass (backoff by minute tick) — and they must not
            // block other pending events meanwhile.
            $candidateQuery = OutboxEvent::query()
                ->pending()
                ->whereIn('event_type', $registered);
            if ($failedThisPass !== []) {
                $candidateQuery->whereNotIn('id', $failedThisPass);
            }
            $candidate = $candidateQuery
                ->orderBy('created_at')
                ->orderBy('id')
                ->first();

            if ($candidate === null) {
                break;
            }

            $id = (string) $candidate->getKey();

            try {
                $dispatched = OutboxEvent::query()->getConnection()->transaction(
                    function () use ($id, $handlers): bool {
                        $event = OutboxEvent::query()->where('id', $id)->lockForUpdate()->first();

                        // Raced (already claimed) or pushed past the cap by a
                        // concurrent failure record — not ours to dispatch.
                        if ($event === null || $event->dispatched_at !== null || $event->attempts >= OutboxEvent::MAX_ATTEMPTS) {
                            return false;
                        }

                        $handlers->dispatch($event);
                        $event->forceFill([
                            'attempts' => $event->attempts + 1,
                            'dispatched_at' => now(),
                            'last_error' => null,
                        ])->save();

                        return true;
                    },
                );

                if ($dispatched) {
                    $counts['dispatched']++;
                }
            } catch (Throwable $e) {
                // Claim rolled back: record the failure so attempts grow
                // and ops can read the cause; the row stays pending and
                // is skipped until the next pass.
                $counts['failed']++;
                $failedThisPass[] = $id;
                $failed = OutboxEvent::query()
                    ->where('id', $id)
                    ->whereNull('dispatched_at')
                    ->first();
                if ($failed !== null) {
                    $failed->forceFill([
                        'attempts' => $failed->attempts + 1,
                        'last_error' => Str::limit($e->getMessage() !== '' ? $e->getMessage() : $e::class, 490),
                    ])->save();
                }
            }
        }

        return $counts;
    }
}
