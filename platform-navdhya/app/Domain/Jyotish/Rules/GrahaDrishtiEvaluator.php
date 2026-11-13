<?php

namespace App\Domain\Jyotish\Rules;

/**
 * Parashari Graha Drishti (Planetary Aspect) Evaluator (T-JY04-04).
 *
 * Implements classical Parashari full and partial aspects (BPHS Chapter 26):
 * - All planets cast 100% aspect on the 7th sign.
 * - Mars casts 100% special aspect on 4th & 8th signs.
 * - Jupiter casts 100% special aspect on 5th & 9th signs.
 * - Saturn casts 100% special aspect on 3rd & 10th signs.
 * - Classical fractional aspects: 3rd/10th (25%), 5th/9th (50%), 4th/8th (75%).
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class GrahaDrishtiEvaluator
{
    /**
     * Calculate aspect strength (0.0 to 1.0) cast by a planet onto a target sign distance (1..12).
     *
     * @param string $planet Casting planet
     * @param int $houseDistance 1-indexed house count from planet to target (1 = conjunct, 7 = opposite)
     * @return float Aspect strength [0.0, 1.0]
     */
    public static function getAspectStrength(string $planet, int $houseDistance): float
    {
        $p = strtolower(trim($planet));
        $dist = (($houseDistance - 1) % 12) + 1;

        if ($dist === 1) {
            // Conjunction, not considered a drishti (aspect)
            return 0.0;
        }

        // All planets cast 100% aspect on the 7th house
        if ($dist === 7) {
            return 1.0;
        }

        // Special aspects (Vishesha Drishti)
        return match ($p) {
            'mars' => match ($dist) {
                4, 8 => 1.0,     // Full special aspect
                5, 9 => 0.50,
                3, 10 => 0.25,
                default => 0.0,
            },
            'jupiter', 'rahu', 'ketu' => match ($dist) {
                5, 9 => 1.0,     // Full special aspect
                4, 8 => 0.75,
                3, 10 => 0.25,
                default => 0.0,
            },
            'saturn' => match ($dist) {
                3, 10 => 1.0,    // Full special aspect
                4, 8 => 0.75,
                5, 9 => 0.50,
                default => 0.0,
            },
            default => match ($dist) {
                4, 8 => 0.75,
                5, 9 => 0.50,
                3, 10 => 0.25,
                default => 0.0,
            },
        };
    }
}

