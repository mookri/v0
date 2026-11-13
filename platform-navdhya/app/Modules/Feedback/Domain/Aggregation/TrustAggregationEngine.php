<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Domain\Aggregation;

/**
 * Pure statistical estimators eliminating naive tiny-sample means (TS-05, ADR-0062).
 *
 * Parity-matched 1-to-1 with packages/feedback/src/quality/quality.ts.
 */
final class TrustAggregationEngine
{
    public const DEFAULT_PRIOR_WEIGHT_C = 10.0;

    public const DEFAULT_HALF_LIFE_DAYS = 90.0;

    /** @var array<string, float> */
    public const CATEGORY_PRIORS = [
        'default' => 4.20,
        'PUJA-LAKSHMI' => 4.30,
        'GRIHA_PRAVESH' => 4.35,
        'SHRADDHA' => 4.40,
        'SATYANARAYAN' => 4.30,
    ];

    public static function categoryPrior(string $category): float
    {
        return self::CATEGORY_PRIORS[$category] ?? self::CATEGORY_PRIORS['default'];
    }

    /**
     * Bayesian shrinkage average: (C * mu + sum(r)) / (C + n).
     *
     * @param  list<int|float>  $ratings
     */
    public static function bayesianAverage(
        array $ratings,
        string $category = 'default',
        float $weightC = self::DEFAULT_PRIOR_WEIGHT_C,
    ): ?float {
        $n = count($ratings);
        if ($n === 0) {
            return null;
        }

        $mu = self::categoryPrior($category);
        $sum = array_sum($ratings);

        return round(($weightC * $mu + $sum) / ($weightC + $n), 2);
    }

    /**
     * Wilson score 95% lower bound for binary proportions (on-time arrival, complaint-free).
     */
    public static function wilsonLowerBound(int $successes, int $total, float $z = 1.96): ?float
    {
        if ($total <= 0) {
            return null;
        }

        $p = max(0.0, min(1.0, $successes / $total));
        $z2 = $z * $z;
        $denom = 1.0 + ($z2 / $total);
        $center = $p + ($z2 / (2.0 * $total));
        $margin = $z * sqrt(($p * (1.0 - $p) + ($z2 / (4.0 * $total))) / $total);

        return round(max(0.0, ($center - $margin) / $denom), 4);
    }

    /**
     * Exponential time-decayed average using w_i = exp(-lambda * age_days).
     *
     * @param  list<array{rating: int|float, age_days: float}>  $samples
     */
    public static function decayedAverage(
        array $samples,
        float $halfLifeDays = self::DEFAULT_HALF_LIFE_DAYS,
    ): ?float {
        if ($samples === []) {
            return null;
        }

        $lambda = log(2.0) / max(1.0, $halfLifeDays);
        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($samples as $sample) {
            $age = max(0.0, (float) $sample['age_days']);
            $w = exp(-$lambda * $age);
            $weightedSum += $w * (float) $sample['rating'];
            $totalWeight += $w;
        }

        if ($totalWeight <= 0.0) {
            return null;
        }

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Proposed publication thresholds — never presented as statistical guarantees (§6.2).
     *
     * @return array{tier: string, label: string, show_numeric_headline: bool}
     */
    public static function confidenceTier(int $sampleCount): array
    {
        if ($sampleCount <= 0) {
            return [
                'tier' => 'NO_REVIEWS',
                'label' => 'No reviews yet',
                'show_numeric_headline' => false,
            ];
        }

        if ($sampleCount <= 4) {
            return [
                'tier' => 'NEW_PROVIDER',
                'label' => 'New provider',
                'show_numeric_headline' => false,
            ];
        }

        if ($sampleCount <= 19) {
            return [
                'tier' => 'EMERGING_CONFIDENCE',
                'label' => 'Emerging confidence',
                'show_numeric_headline' => true,
            ];
        }

        return [
            'tier' => 'ESTABLISHED_SAMPLE',
            'label' => 'Established sample',
            'show_numeric_headline' => true,
        ];
    }
}

