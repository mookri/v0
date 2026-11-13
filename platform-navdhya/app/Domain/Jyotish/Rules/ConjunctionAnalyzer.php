<?php

namespace App\Domain\Jyotish\Rules;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;

/**
 * Planetary Conjunction (Graha Yuti) Analyzer (T-JY04-05).
 *
 * Detects:
 * - Same-sign conjunctions
 * - Orb-bounded tight (<= 1°), close (<= 3°), and wide (<= 8°) conjunctions
 * - Cross-sign border wraparounds (e.g., 359° & 1°)
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class ConjunctionAnalyzer implements ZodiacConstants
{
    public const ORB_TIGHT = 1.0;
    public const ORB_CLOSE = 3.2;
    public const ORB_WIDE = 8.0;

    /**
     * Analyze all planetary conjunctions across the chart.
     *
     * @param array<string, float> $planetaryLongitudes Keyed by graha name
     * @return list<array{
     *     planet_a: string,
     *     planet_b: string,
     *     separation_degrees: float,
     *     same_rashi: bool,
     *     rashi_index: int|null,
     *     tightness: string,
     *     is_exact: bool
     * }>
     */
    public static function analyze(array $planetaryLongitudes): array
    {
        $conjunctions = [];
        $planets = array_keys($planetaryLongitudes);
        $count = count($planets);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $p1 = $planets[$i];
                $p2 = $planets[$j];

                $lon1 = Angle::normalize($planetaryLongitudes[$p1]);
                $lon2 = Angle::normalize($planetaryLongitudes[$p2]);

                $rashi1 = (int) floor($lon1 / self::RASHI_SPAN_DEGREES);
                $rashi2 = (int) floor($lon2 / self::RASHI_SPAN_DEGREES);
                $sameRashi = ($rashi1 === $rashi2);

                $sep = abs($lon1 - $lon2);
                if ($sep > 180.0) {
                    $sep = 360.0 - $sep;
                }

                // If in same rashi OR within wide conjunction orb
                if ($sameRashi || $sep <= self::ORB_WIDE) {
                    $tightness = match (true) {
                        $sep <= self::ORB_TIGHT => 'tight',
                        $sep <= self::ORB_CLOSE => 'close',
                        default => 'wide',
                    };

                    $conjunctions[] = [
                        'planet_a' => $p1,
                        'planet_b' => $p2,
                        'separation_degrees' => round($sep, 4),
                        'same_rashi' => $sameRashi,
                        'rashi_index' => $sameRashi ? $rashi1 : null,
                        'tightness' => $tightness,
                        'is_exact' => $sep <= 0.05,
                    ];
                }
            }
        }

        return $conjunctions;
    }
}

