<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Recommendations\Models\RecoInteraction;
use App\Modules\Recommendations\Models\RecoTransition;

class MarkovTransitionModel
{
    /**
     * Train transition probabilities from sequential session interactions.
     *
     * @return int Number of transition rows written
     */
    public function train(): int
    {
        $sessions = RecoInteraction::whereNotNull('session_id')
            ->orderBy('interacted_at')
            ->get(['session_id', 'entity_id', 'interacted_at'])
            ->groupBy('session_id');

        $transitions = [];
        $sourceTotals = [];

        foreach ($sessions as $sessionId => $events) {
            $sorted = $events->values();
            $count = count($sorted);

            for ($i = 0; $i + 1 < $count; $i++) {
                $src = (string) $sorted[$i]->entity_id;
                $tgt = (string) $sorted[$i + 1]->entity_id;

                if ($src === $tgt) {
                    continue;
                }

                $pair = $src . '=>' . $tgt;
                $transitions[$pair] = ($transitions[$pair] ?? 0) + 1;
                $sourceTotals[$src] = ($sourceTotals[$src] ?? 0) + 1;
            }
        }

        $written = 0;
        foreach ($transitions as $pair => $count) {
            [$src, $tgt] = explode('=>', $pair);
            $totalFromSrc = $sourceTotals[$src] ?? $count;
            $pEstimate = round($count / $totalFromSrc, 4);

            RecoTransition::updateOrCreate(
                [
                    'source_id' => $src,
                    'target_id' => $tgt,
                ],
                [
                    'count' => $count,
                    'p_estimate' => $pEstimate,
                ]
            );
            $written++;
        }

        return $written;
    }

    /**
     * Predict top next candidates given current entity id.
     *
     * @return list<array{entity_id: string, probability: float}>
     */
    public function predictNext(string $currentEntityId, int $limit = 5): array
    {
        $rows = RecoTransition::where('source_id', $currentEntityId)
            ->orderByDesc('p_estimate')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'entity_id' => (string) $r->target_id,
            'probability' => (float) $r->p_estimate,
        ])->all();
    }
}

