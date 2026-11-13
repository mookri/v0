<?php

namespace App\Domain\Jyotish\Chart;

use App\Domain\Jyotish\Astronomy\AscendantSolver;
use App\Domain\Jyotish\Astronomy\AyanamsaEngine;
use App\Domain\Jyotish\Astronomy\SiderealLayer;
use App\Domain\Jyotish\Chart\HouseSystem\HouseCuspCalculator;
use App\Domain\Jyotish\Ephemeris\EphemerisProviderInterface;
use App\Domain\Jyotish\Ephemeris\MeeusSeriesProvider;

/**
 * Canonical D1 Rashi & Kundli Chart Generator (T-JY03-04).
 *
 * Coordinates ephemeris snapshotting, sidereal conversion, Lagna solving,
 * boundary indexing, house cusp calculation, and Bhava Chalit mapping into
 * an immutable, deterministic astronomical chart payload.
 * Pure domain service — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class D1ChartGenerator
{
    /**
     * Generate canonical D1 chart payload.
     *
     * @param float $jdUt1 Julian Day UT1
     * @param float $jdTt Julian Day TT
     * @param float $latitude Geographic latitude [-90, +90]
     * @param float $longitude Geographic longitude [-180, +180]
     * @param string $ayanamsaSystem Canonical ayanamsa code (default LAHIRI)
     * @param string $houseSystemCode Canonical house system code (default WHOLE_SIGN)
     * @param EphemerisProviderInterface|null $provider Optional ephemeris provider override
     * @return array<string, mixed>
     */
    public static function generate(
        float $jdUt1,
        float $jdTt,
        float $latitude,
        float $longitude,
        string $ayanamsaSystem = AyanamsaEngine::SYSTEM_LAHIRI,
        string $houseSystemCode = HouseCuspCalculator::SYSTEM_WHOLE_SIGN,
        ?EphemerisProviderInterface $provider = null
    ): array {
        $ephemeris = $provider ?? new MeeusSeriesProvider();

        // 1. Tropical 9-graha snapshot
        $tropicalSnapshot = $ephemeris->computeSnapshot($jdTt, $jdUt1);

        // 2. Sidereal conversion pipeline
        $conversion = SiderealLayer::convert($tropicalSnapshot, $ayanamsaSystem);
        $siderealSnapshot = $conversion['snapshot'];
        $ayanamsaMeta = $conversion['ayanamsa'];
        $ayanamsaDeg = $ayanamsaMeta['ayanamsa_degrees'];

        // 3. Lagna & MC horizon solver
        $lagna = AscendantSolver::solve($jdUt1, $jdTt, $latitude, $longitude, $ayanamsaDeg);
        $lagnaBoundary = BoundaryIndexer::index($lagna['sidereal_ascendant']);

        // 4. House Cusps
        $houseSystem = HouseCuspCalculator::forSystem($houseSystemCode);
        $cusps = $houseSystem->calculateCusps(
            $lagna['sidereal_ascendant'],
            $lagna['sidereal_mc'],
            $latitude,
            $lagna['ramc_degrees'],
            $lagna['obliquity_degrees']
        );

        // 5. Planetary positions & boundary indexing
        $planetaryLongitudes = [];
        $indexedPositions = [];

        foreach ($siderealSnapshot->getGrahaPositions() as $graha => $pos) {
            $lon = $pos['longitude'];
            $planetaryLongitudes[$graha] = $lon;
            $boundary = BoundaryIndexer::index($lon);

            // Whole-sign bhava relative to Lagna Rashi
            $d1Bhava = (($boundary['rashi']['index'] - $lagnaBoundary['rashi']['index'] + 12) % 12) + 1;

            $indexedPositions[$graha] = [
                'name' => $graha,
                'sidereal_degrees' => $lon,
                'dms' => $boundary['rashi']['dms_in_rashi'],
                'speed_longitude' => $pos['speed_longitude'],
                'is_retrograde' => $pos['speed_longitude'] < 0.0,
                'latitude' => $pos['latitude'],
                'distance' => $pos['distance'],
                'd1_bhava' => $d1Bhava,
                'rashi' => $boundary['rashi'],
                'nakshatra' => $boundary['nakshatra'],
            ];
        }

        // 6. Bhava Chalit projection
        $chalit = BhavaChalitGenerator::generate(
            $houseSystem,
            $lagna['sidereal_ascendant'],
            $lagna['sidereal_mc'],
            $planetaryLongitudes,
            $latitude,
            $lagna['ramc_degrees'],
            $lagna['obliquity_degrees']
        );

        return [
            'chart_type' => 'D1',
            'ayanamsa' => $ayanamsaMeta,
            'house_system' => [
                'code' => $houseSystem->systemCode(),
                'name' => $houseSystem->systemName(),
            ],
            'lagna' => [
                'sidereal_degrees' => $lagna['sidereal_ascendant'],
                'tropical_degrees' => $lagna['tropical_ascendant'],
                'dms' => $lagna['dms_ascendant'],
                'boundary' => $lagnaBoundary,
                'horizon_status' => $lagna['status']->value,
            ],
            'midheaven' => [
                'sidereal_degrees' => $lagna['sidereal_mc'],
                'tropical_degrees' => $lagna['tropical_mc'],
                'dms' => $lagna['dms_mc'],
            ],
            'positions' => $indexedPositions,
            'houses' => $cusps,
            'bhava_chalit' => $chalit->toArray(),
        ];
    }
}

