<?php

namespace App\Domain\Jyotish\Chart;

use App\Domain\Jyotish\Chart\HouseSystem\HouseSystemInterface;
use App\Domain\Jyotish\Chart\Models\BhavaChalitChart;
use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;
use RuntimeException;

/**
 * High-Precision Bhava Chalit Generator (T-JY03-03).
 *
 * Maps planetary sidereal longitudes into 12 Bhavas based on house sandhi boundaries
 * without modifying original celestial D1 rashi longitudes (AC-T-JY03-03).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class BhavaChalitGenerator implements ZodiacConstants
{
    /**
     * Generate Bhava Chalit chart from cusps and planetary longitudes.
     *
     * @param HouseSystemInterface $houseSystem
     * @param float $lagnaDegrees Sidereal Ascendant degree
     * @param float $mcDegrees Sidereal Midheaven degree
     * @param array<string, float> $planetaryLongitudes Keyed by body name ('sun', 'moon', etc.)
     * @param float $latitude Observer geographic latitude
     * @param float $ramcDegrees Local Sidereal Time
     * @param float $obliquityDegrees True obliquity
     * @return BhavaChalitChart
     */
    public static function generate(
        HouseSystemInterface $houseSystem,
        float $lagnaDegrees,
        float $mcDegrees,
        array $planetaryLongitudes,
        float $latitude = 0.0,
        float $ramcDegrees = 0.0,
        float $obliquityDegrees = 23.44
    ): BhavaChalitChart {
        $cusps = $houseSystem->calculateCusps(
            $lagnaDegrees,
            $mcDegrees,
            $latitude,
            $ramcDegrees,
            $obliquityDegrees
        );

        $lagnaNorm = Angle::normalize($lagnaDegrees);
        $lagnaRashi = (int) floor($lagnaNorm / self::RASHI_SPAN_DEGREES);

        // Initialize bhavas array
        $bhavas = [];
        for ($h = 1; $h <= 12; $h++) {
            $bhavas[$h] = [
                'house' => $h,
                'madhya_degrees' => $cusps[$h]['cusp_degrees'],
                'madhya_dms' => $cusps[$h]['dms'],
                'sandhi_start' => $cusps[$h]['sandhi_start'],
                'sandhi_end' => $cusps[$h]['sandhi_end'],
                'span_degrees' => $cusps[$h]['span_degrees'],
                'planets' => [],
            ];
        }

        $placements = [];

        foreach ($planetaryLongitudes as $planet => $deg) {
            $normalizedDeg = Angle::normalize($deg);
            $rashiIndex = (int) floor($normalizedDeg / self::RASHI_SPAN_DEGREES);
            if ($rashiIndex >= 12) {
                $rashiIndex = 11;
            }
            $rashiMeta = self::RASHIS[$rashiIndex];

            // Whole-sign D1 bhava relative to Lagna
            $d1Bhava = (($rashiIndex - $lagnaRashi + 12) % 12) + 1;

            // Locate Chalit Bhava via sandhi intervals
            $chalitBhava = null;
            for ($h = 1; $h <= 12; $h++) {
                if (self::isAngleInArc($normalizedDeg, $cusps[$h]['sandhi_start'], $cusps[$h]['sandhi_end'])) {
                    $chalitBhava = $h;
                    break;
                }
            }

            if ($chalitBhava === null) {
                // Epsilon fallback to the closest house
                $chalitBhava = 1;
            }

            // Determine shift relative to whole-sign D1 bhava
            $diff = $chalitBhava - $d1Bhava;
            if ($diff < -6) {
                $diff += 12;
            } elseif ($diff > 6) {
                $diff -= 12;
            }

            $shift = match ($diff) {
                0 => 'none',
                1 => 'forward_1',
                -1 => 'backward_1',
                default => "shift_{$diff}",
            };

            $bhavas[$chalitBhava]['planets'][] = $planet;

            $placements[$planet] = [
                'planet' => $planet,
                'sidereal_degrees' => $normalizedDeg,
                'dms' => (new Angle($normalizedDeg))->toDms(),
                'rashi_index' => $rashiIndex,
                'rashi_name' => $rashiMeta['name'],
                'd1_bhava' => $d1Bhava,
                'chalit_bhava' => $chalitBhava,
                'shift' => $shift,
            ];
        }

        return new BhavaChalitChart(
            houseSystem: $houseSystem->systemCode(),
            bhavas: $bhavas,
            planetaryPlacements: $placements
        );
    }

    /**
     * Check if an angle lies inside the half-open circular arc [start, end).
     */
    public static function isAngleInArc(float $angle, float $start, float $end): bool
    {
        $a = Angle::normalize($angle);
        $s = Angle::normalize($start);
        $e = Angle::normalize($end);

        if ($s < $e) {
            return $a >= $s && $a < $e;
        }

        // Arc spans 0° (e.g. 350° to 20°)
        return $a >= $s || $a < $e;
    }
}

