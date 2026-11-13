<?php

namespace App\Domain\Delivery\Console;

use App\Domain\Delivery\State\DeliveryStateMachine;
use App\Modules\Customers\Models\NotificationDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Reconciles unacknowledged or ambiguous delivery states (E-CM-02 / Task T-CM02-04).
 * Walks SENT and DELIVERY_UNKNOWN records older than threshold and transitions
 * stale messages to EXPIRED after the maximum retention window (24h).
 */
final class DeliveryReconcileCommand extends Command
{
    protected $signature = 'delivery:reconcile
        {--limit=100 : Maximum delivery rows to reconcile in one pass}
        {--threshold-minutes=15 : Minimum age in minutes to consider for reconciliation}
        {--expire-hours=24 : Age in hours beyond which unresolved deliveries expire}';

    protected $description = 'Reconcile pending/unacknowledged delivery states and expire abandoned rows';

    public function handle(DeliveryStateMachine $stateMachine): int
    {
        $limit = (int) $this->option('limit');
        $thresholdMinutes = (int) $this->option('threshold-minutes');
        $expireHours = (int) $this->option('expire-hours');

        $now = CarbonImmutable::now();
        $cutoff = $now->subMinutes($thresholdMinutes);
        $expireCutoff = $now->subHours($expireHours);

        $deliveries = NotificationDelivery::query()
            ->whereIn('status', [
                NotificationDelivery::STATUS_SENT,
                'sent',
                NotificationDelivery::STATUS_DELIVERY_UNKNOWN,
            ])
            ->where('created_at', '<=', $cutoff)
            ->limit($limit)
            ->get();

        $reconciledCount = 0;
        $expiredCount = 0;

        foreach ($deliveries as $delivery) {
            if ($delivery->created_at !== null && $delivery->created_at->lte($expireCutoff)) {
                $stateMachine->apply($delivery, NotificationDelivery::STATUS_EXPIRED);
                $expiredCount++;
            } else {
                // Update reconciled_at to record that reconciler inspected this record
                $delivery->forceFill(['reconciled_at' => $now])->save();
                $reconciledCount++;
            }
        }

        $this->info("Reconciliation pass complete: {$reconciledCount} inspected, {$expiredCount} expired.");

        return self::SUCCESS;
    }
}

