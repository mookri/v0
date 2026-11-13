<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain;

use App\Modules\Incident\Application\IncidentCorrelationService;
use App\Modules\Incident\Models\Incident;
use Carbon\CarbonImmutable;

/**
 * Rule-based operational cluster detector (T-TS05-03).
 *
 * Scans recent incidents/impacts by cluster key (e.g., venue, streaming node, corridor)
 * and proposes correlations for human commander review — never auto-merges.
 */
final class OperationalAnomalyDetector
{
    public function __construct(
        private readonly IncidentCorrelationService $correlations,
    ) {
    }

    /**
     * @param  list<array{booking_id: string, cluster_key: string}>  $signals
     * @return int Number of correlation proposals created
     */
    public function proposeClusterCorrelations(string $tenantId, string $primaryIncidentId, array $signals, int $threshold = 2): int
    {
        $grouped = [];
        foreach ($signals as $signal) {
            $grouped[$signal['cluster_key']][] = $signal['booking_id'];
        }

        $created = 0;
        $now = CarbonImmutable::now('UTC');
        $otherRecent = Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $primaryIncidentId)
            ->where('detected_at', '>=', $now->subHours(6)->toDateTimeString())
            ->first();

        foreach ($grouped as $clusterKey => $bookingIds) {
            if (count($bookingIds) < $threshold) {
                continue;
            }

            foreach ($bookingIds as $bookingId) {
                $this->correlations->propose(
                    $primaryIncidentId,
                    (string) $clusterKey,
                    min(0.95, 0.55 + (0.10 * count($bookingIds))),
                    [
                        'cluster_key' => $clusterKey,
                        'cluster_size' => count($bookingIds),
                        'window_hours' => 6,
                    ],
                    $bookingId,
                    $otherRecent !== null ? (string) $otherRecent->getKey() : null,
                );
                $created++;
            }
        }

        return $created;
    }
}

