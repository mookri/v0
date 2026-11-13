<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Recommendations\Models\RecoInteraction;
use App\Modules\Recommendations\Models\RecoItemNeighbor;
use Illuminate\Support\Str;

class CollaborativeFilteringAggregator
{
    /**
     * Compute co-occurrence matrix from session interactions and write to reco_item_neighbors.
     *
     * @return int Number of neighbor rows updated
     */
    public function compute(): int
    {
        // 1. Group interactions by session
        $sessions = RecoInteraction::whereNotNull('session_id')
            ->get(['session_id', 'entity_type', 'entity_id'])
            ->groupBy('session_id');

        $coOccurrences = [];
        $itemCounts = [];

        foreach ($sessions as $sessionId => $events) {
            $uniqueEntities = $events->unique(fn ($e) => $e->entity_type . ':' . $e->entity_id)->values();
            $count = count($uniqueEntities);

            for ($i = 0; $i < $count; $i++) {
                $e1 = $uniqueEntities[$i];
                $k1 = $e1->entity_type . '|' . $e1->entity_id;
                $itemCounts[$k1] = ($itemCounts[$k1] ?? 0) + 1;

                for ($j = $i + 1; $j < $count; $j++) {
                    $e2 = $uniqueEntities[$j];
                    $k2 = $e2->entity_type . '|' . $e2->entity_id;

                    $pair1 = $k1 . '=>' . $k2;
                    $pair2 = $k2 . '=>' . $k1;

                    $coOccurrences[$pair1] = ($coOccurrences[$pair1] ?? 0) + 1;
                    $coOccurrences[$pair2] = ($coOccurrences[$pair2] ?? 0) + 1;
                }
            }
        }

        $updated = 0;
        foreach ($coOccurrences as $pair => $coCount) {
            [$from, $to] = explode('=>', $pair);
            [$srcType, $srcId] = explode('|', $from);
            [$nbrType, $nbrId] = explode('|', $to);

            // Jaccard similarity: co_count / (count_A + count_B - co_count)
            $denom = ($itemCounts[$from] ?? 1) + ($itemCounts[$to] ?? 1) - $coCount;
            $jaccard = $denom > 0 ? round($coCount / $denom, 3) : 0.000;

            RecoItemNeighbor::updateOrCreate(
                [
                    'source_type' => $srcType,
                    'source_id' => $srcId,
                    'neighbor_type' => $nbrType,
                    'neighbor_id' => $nbrId,
                ],
                [
                    'sim_final' => $jaccard,
                    'relation_kind' => $jaccard >= 0.5 ? 'complementary' : 'substitute',
                ]
            );
            $updated++;
        }

        return $updated;
    }
}

