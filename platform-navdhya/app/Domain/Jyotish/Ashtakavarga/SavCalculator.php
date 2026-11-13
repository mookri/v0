<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Ashtakavarga;

use App\Domain\Jyotish\Ashtakavarga\Models\BavMatrix;

final readonly class SavCalculator
{
    /**
     * Compute Sarvashtakavarga (SAV) by aggregating 7 planetary BAV matrices across 12 signs.
     * Classical Parashari system constant total = 337 bindus across all 12 signs (doc 01 §3, AC-T-JY08-04).
     * Strict rule: Rahu and Ketu are NEVER added to SAV.
     *
     * @param array<string, BavMatrix> $bavMatrices [sun, moon, mars, mercury, jupiter, venus, saturn]
     * @return array{
     *     sign_totals: array<int, int>,
     *     total_bindus: int,
     *     average_per_sign: float,
     *     bav_summary: array<string, int>
     * }
     */
    public function calculateSav(array $bavMatrices): array
    {
        $signTotals = array_fill(1, 12, 0);
        $bavSummary = [];

        $classical7 = ['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn'];

        foreach ($classical7 as $g) {
            if (!isset($bavMatrices[$g])) {
                throw new \InvalidArgumentException("Missing required classical BAV matrix for {$g}");
            }

            $matrix = $bavMatrices[$g];
            $bavSummary[$g] = $matrix->getTotalBindus();

            foreach ($matrix->signBindus as $sign => $bindus) {
                $signTotals[$sign] += $bindus;
            }
        }

        $totalBindus = array_sum($signTotals);

        return [
            'sign_totals' => $signTotals,
            'total_bindus' => $totalBindus,
            'average_per_sign' => round($totalBindus / 12.0, 2), // 28.08 bindus
            'bav_summary' => $bavSummary,
        ];
    }
}

