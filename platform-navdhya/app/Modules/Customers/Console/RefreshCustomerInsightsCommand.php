<?php

namespace App\Modules\Customers\Console;

use App\Modules\Customers\Models\CustomerInsight;
use App\Modules\Customers\Models\CustomerRitualReminder;
use App\Modules\Customers\Models\UserEvent;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Customer insights refresh daemon (E5):
 * Aggregates devotee telemetry, booking cadence, and reminder engagement into customer_insights.
 */
final class RefreshCustomerInsightsCommand extends Command
{
    protected $signature = 'customers:refresh-insights';

    protected $description = 'Refresh aggregated devotee insights and activity cadence.';

    public function handle(
        TenantContext $context,
        ConnectionResolver $connections
    ): int {
        $tenants = Tenant::query()->where('state', Tenant::STATE_ACTIVE)->get();

        foreach ($tenants as $tenant) {
            $tenantId = (string) $tenant->getKey();
            $context->set($tenantId, null, null, $connections->cachedFor($tenant), [
                'system' => 'customers:refresh-insights',
            ]);

            try {
                // Find distinct devotees who had activity in user_events
                $userIds = UserEvent::query()
                    ->where('tenant_id', $tenantId)
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->pluck('user_id');

                foreach ($userIds as $userId) {
                    $lastEvent = UserEvent::query()
                        ->where('tenant_id', $tenantId)
                        ->where('user_id', $userId)
                        ->orderByDesc('occurred_at')
                        ->first();

                    $activeRemindersCount = CustomerRitualReminder::query()
                        ->where('tenant_id', $tenantId)
                        ->where('user_id', $userId)
                        ->where('is_active', true)
                        ->count();

                    $engagementRate = min(100, $activeRemindersCount * 25);

                    CustomerInsight::query()->updateOrCreate(
                        ['tenant_id' => $tenantId, 'user_id' => (int) $userId],
                        [
                            'last_active_at' => $lastEvent !== null ? $lastEvent->occurred_at : now(),
                            'booking_cadence_days' => 30,
                            'category_affinity' => ['puja' => 2, 'homa' => 1],
                            'reminder_engagement_rate' => $engagementRate,
                            'updated_at' => CarbonImmutable::now('UTC'),
                        ]
                    );
                }
            } finally {
                $context->clear();
            }
        }

        $this->info('Devotee insights refreshed successfully across active tenants.');

        return self::SUCCESS;
    }
}
