<?php

namespace App\Domain\Delivery\Reliability;

use App\Domain\Delivery\Models\CommunicationDelivery;

final class DeliveryTelemetryMetrics
{
    /**
     * Compute operational delivery metrics for a tenant or platform-wide.
     *
     * @return array{
     *     total: int,
     *     by_channel: array<string, int>,
     *     by_status: array<string, int>,
     *     delivery_rate_percent: float,
     *     suppression_rate_percent: float,
     *     failure_rate_percent: float,
     *     average_delivery_latency_seconds: float
     * }
     */
    public function calculate(?string $tenantId = null): array
    {
        $query = CommunicationDelivery::query();
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $allDeliveries = $query->get();
        $total = $allDeliveries->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'by_channel' => [],
                'by_status' => [],
                'delivery_rate_percent' => 0.0,
                'suppression_rate_percent' => 0.0,
                'failure_rate_percent' => 0.0,
                'average_delivery_latency_seconds' => 0.0,
            ];
        }

        $byChannel = $allDeliveries->groupBy('channel')->map->count()->all();
        $byStatus = $allDeliveries->groupBy('status')->map->count()->all();

        $deliveredCount = ($byStatus[CommunicationDelivery::STATUS_DELIVERED] ?? 0)
            + ($byStatus[CommunicationDelivery::STATUS_READ] ?? 0);
        $suppressedCount = $byStatus[CommunicationDelivery::STATUS_SUPPRESSED] ?? 0;
        $failedCount = $byStatus[CommunicationDelivery::STATUS_FAILED] ?? 0;

        $deliveryRate = round(($deliveredCount / $total) * 100, 2);
        $suppressionRate = round(($suppressedCount / $total) * 100, 2);
        $failureRate = round(($failedCount / $total) * 100, 2);

        // Latency calculation: time from sent_at to delivered_at in seconds
        $deliveriesWithLatency = $allDeliveries->filter(fn (CommunicationDelivery $d) => $d->sent_at !== null && $d->delivered_at !== null);
        $avgLatency = 0.0;
        if ($deliveriesWithLatency->isNotEmpty()) {
            $totalLatencySeconds = $deliveriesWithLatency->sum(fn (CommunicationDelivery $d) => abs($d->delivered_at->getTimestamp() - $d->sent_at->getTimestamp()));
            $avgLatency = round($totalLatencySeconds / $deliveriesWithLatency->count(), 2);
        }

        return [
            'total' => $total,
            'by_channel' => $byChannel,
            'by_status' => $byStatus,
            'delivery_rate_percent' => $deliveryRate,
            'suppression_rate_percent' => $suppressionRate,
            'failure_rate_percent' => $failureRate,
            'average_delivery_latency_seconds' => $avgLatency,
        ];
    }
}
