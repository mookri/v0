<?php

namespace App\Modules\Recommendations\Services;

class RrfCandidateCombiner
{
    private const DEFAULT_K = 60;

    /**
     * Merge multiple candidate lists using Reciprocal Rank Fusion (k=60).
     *
     * @param array<string, list<array{document: mixed, reason_code: string, reason_label: string}>> $sourceRankings
     * @param int $k Constant damping factor (default 60)
     * @return list<array{document: mixed, score: float, reason_code: string, reason_label: string}>
     */
    public function combine(array $sourceRankings, int $k = self::DEFAULT_K): array
    {
        $scores = [];
        $candidates = [];
        $primaryReasons = [];

        foreach ($sourceRankings as $sourceName => $rankedList) {
            foreach ($rankedList as $rank => $item) {
                $doc = $item['document'];
                $key = $doc->entity_type . ':' . $doc->entity_id;

                $rrfContribution = 1.0 / ($k + ($rank + 1));
                $scores[$key] = ($scores[$key] ?? 0.0) + $rrfContribution;

                if (! isset($candidates[$key])) {
                    $candidates[$key] = $doc;
                    $primaryReasons[$key] = [
                        'code' => $item['reason_code'],
                        'label' => $item['reason_label'],
                    ];
                }
            }
        }

        arsort($scores);

        $results = [];
        foreach ($scores as $key => $finalScore) {
            $results[] = [
                'document' => $candidates[$key],
                'score' => round($finalScore, 6),
                'reason_code' => $primaryReasons[$key]['code'],
                'reason_label' => $primaryReasons[$key]['label'],
            ];
        }

        return $results;
    }
}

