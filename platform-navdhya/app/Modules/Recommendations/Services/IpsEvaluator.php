<?php

namespace App\Modules\Recommendations\Services;

class IpsEvaluator
{
    private const GAMMA = 0.55; // Position bias decay parameter

    /**
     * Compute examination propensity for a display position (0-based rank).
     * p_k = 1 / (k + 1)^gamma
     */
    public function getPropensity(int $position): float
    {
        $k = max(0, $position);

        return 1.0 / pow($k + 1.0, self::GAMMA);
    }

    /**
     * Evaluate a policy on logged telemetry interactions using Inverse Propensity Scoring.
     *
     * @param list<array{
     *   session_id: string,
     *   entity_id: string,
     *   position: int,
     *   clicked: bool,
     *   context: array<string, mixed>
     * }> $loggedData
     * @param callable(array<string, mixed>): list<string> $policyPredictor
     * @return array{
     *   sample_size: int,
     *   logged_clicks: int,
     *   raw_ctr: float,
     *   ips_score: float,
     *   snips_score: float
     * }
     */
    public function evaluate(array $loggedData, callable $policyPredictor): array
    {
        $sampleSize = count($loggedData);
        if ($sampleSize === 0) {
            return [
                'sample_size' => 0,
                'logged_clicks' => 0,
                'raw_ctr' => 0.0,
                'ips_score' => 0.0,
                'snips_score' => 0.0,
            ];
        }

        $totalClicks = 0;
        $ipsSum = 0.0;
        $weightSum = 0.0;

        foreach ($loggedData as $log) {
            $isClick = (bool) $log['clicked'];
            if ($isClick) {
                $totalClicks++;
            }

            $predictedItems = $policyPredictor($log['context']);
            $loggedEntityId = (string) $log['entity_id'];
            $pos = (int) $log['position'];

            // Check if policy would have recommended this logged item in top slots (e.g. top 3)
            $matches = in_array($loggedEntityId, array_slice($predictedItems, 0, 3), true);

            if ($matches) {
                $propensity = max(0.01, $this->getPropensity($pos));
                $invPropensity = 1.0 / $propensity;

                $weightSum += $invPropensity;
                if ($isClick) {
                    $ipsSum += $invPropensity;
                }
            }
        }

        $rawCtr = round($totalClicks / $sampleSize, 4);
        $ipsScore = round($ipsSum / $sampleSize, 4);
        $snipsScore = $weightSum > 0 ? round($ipsSum / $weightSum, 4) : 0.0;

        return [
            'sample_size' => $sampleSize,
            'logged_clicks' => $totalClicks,
            'raw_ctr' => $rawCtr,
            'ips_score' => $ipsScore,
            'snips_score' => $snipsScore,
        ];
    }
}

