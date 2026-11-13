<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Recommendations\Models\RecoItemNeighbor;

class MmrReranker
{
    private const DEFAULT_LAMBDA = 0.70;

    /**
     * Rerank candidates using Maximal Marginal Relevance (MMR).
     *
     * @param list<array{document: mixed, score: float, reason_code: string, reason_label: string}> $candidates
     * @param int $limit
     * @param float $lambda
     * @return list<array{document: mixed, score: float, reason_code: string, reason_label: string}>
     */
    public function rerank(array $candidates, int $limit, float $lambda = self::DEFAULT_LAMBDA): array
    {
        if (empty($candidates)) {
            return [];
        }

        $remaining = $candidates;
        $selected = [];

        // Normalize scores to [0.0, 1.0]
        $maxScore = max(array_column($candidates, 'score')) ?: 1.0;

        // Preload neighbor relationships for candidates
        $candidateIds = array_map(fn ($c) => (string) $c['document']->entity_id, $candidates);
        $neighbors = RecoItemNeighbor::whereIn('source_id', $candidateIds)
            ->whereIn('neighbor_id', $candidateIds)
            ->get()
            ->groupBy('source_id');

        while (! empty($remaining) && count($selected) < $limit) {
            $bestCandidate = null;
            $bestIndex = -1;
            $bestMmrScore = -INF;

            foreach ($remaining as $i => $item) {
                $doc = $item['document'];
                $normalizedScore = (float) $item['score'] / $maxScore;

                $maxSim = 0.0;
                foreach ($selected as $s) {
                    $sDoc = $s['document'];
                    $sim = $this->calculateSimilarity($doc, $sDoc, $neighbors);
                    if ($sim > $maxSim) {
                        $maxSim = $sim;
                    }
                }

                $mmr = ($lambda * $normalizedScore) - ((1.0 - $lambda) * $maxSim);

                if ($mmr > $bestMmrScore) {
                    $bestMmrScore = $mmr;
                    $bestCandidate = $item;
                    $bestIndex = $i;
                }
            }

            if ($bestCandidate !== null) {
                $selected[] = $bestCandidate;
                unset($remaining[$bestIndex]);
                $remaining = array_values($remaining);
            } else {
                break;
            }
        }

        return $selected;
    }

    private function calculateSimilarity(mixed $docA, mixed $docB, mixed $neighborsGroup): float
    {
        // 1. Check precomputed neighbor graph
        $sourceId = (string) $docA->entity_id;
        $targetId = (string) $docB->entity_id;

        if (isset($neighborsGroup[$sourceId])) {
            $neighborRow = $neighborsGroup[$sourceId]->firstWhere('neighbor_id', $targetId);
            if ($neighborRow) {
                // Complementary items (e.g. samagri kit or related havan) have low redundancy
                if ($neighborRow->relation_kind === 'complementary') {
                    return 0.10;
                }
                return (float) $neighborRow->sim_final;
            }
        }

        // 2. Fallback heuristic: same type and category
        $sameType = $docA->entity_type === $docB->entity_type;
        $sameSubtitle = ! empty($docA->subtitle) && $docA->subtitle === $docB->subtitle;

        if ($sameType && $sameSubtitle) {
            return 0.85; // Heavy penalty for duplicate substitute in same category
        }
        if ($sameType) {
            return 0.40;
        }

        return 0.10;
    }
}

