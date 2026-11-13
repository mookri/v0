<?php

namespace App\Domain\Jyotish\Astronomy;

use App\Domain\Jyotish\Ephemeris\EphemerisSnapshot;
use App\Domain\Jyotish\Support\Angle;

/**
 * Unified Sidereal Conversion Pipeline (T-JY02-04, ADR-JY-02).
 *
 * Converts tropical geocentric ephemeris snapshots into sidereal coordinates
 * via versioned Ayanamsa models. Enforces the single canonical transformation
 * path across the entire Jyotish engine.
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class SiderealLayer
{
    /**
     * Convert a tropical EphemerisSnapshot into a sidereal EphemerisSnapshot.
     *
     * @param EphemerisSnapshot $tropicalSnapshot
     * @param string $ayanamsaSystem (LAHIRI, RAMAN, KRISHNAMURTI, etc.)
     * @param bool $isTrueApparent If true, applies nutation in longitude
     * @return array{
     *     snapshot: EphemerisSnapshot,
     *     ayanamsa: array{system: string, ayanamsa_degrees: float, ayanamsa_dms: string, nutation_degrees: float, epoch_reference: string, rate_per_century: float}
     * }
     */
    public static function convert(
        EphemerisSnapshot $tropicalSnapshot,
        string $ayanamsaSystem = AyanamsaEngine::SYSTEM_LAHIRI,
        bool $isTrueApparent = false
    ): array {
        $t = ($tropicalSnapshot->getJdTt() - 2451545.0) / 36525.0;
        $ayanamsa = AyanamsaEngine::calculate($t, $ayanamsaSystem, $isTrueApparent);
        $ayanamsaDeg = $ayanamsa['ayanamsa_degrees'];

        $siderealGrahas = [];

        foreach ($tropicalSnapshot->getGrahaPositions() as $graha => $pos) {
            $siderealLon = Angle::normalize($pos['longitude'] - $ayanamsaDeg);

            $siderealGrahas[$graha] = [
                'longitude' => $siderealLon,
                'latitude' => $pos['latitude'],
                'distance' => $pos['distance'],
                'speed_longitude' => $pos['speed_longitude'], // Derivative is invariant under constant translation
                'speed_latitude' => $pos['speed_latitude'],
                'speed_distance' => $pos['speed_distance'],
            ];
        }

        $siderealSnapshot = new EphemerisSnapshot(
            jdTt: $tropicalSnapshot->getJdTt(),
            jdUt1: $tropicalSnapshot->getJdUt1(),
            grahas: $siderealGrahas
        );

        return [
            'snapshot' => $siderealSnapshot,
            'ayanamsa' => $ayanamsa,
        ];
    }
}

