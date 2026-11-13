<?php

namespace App\Domain\Calendar\Astronomy;

use Carbon\CarbonImmutable;

/**
 * Sidereal Time calculations:
 * - GMST: Greenwich Mean Sidereal Time
 * - LMST: Local Mean Sidereal Time (at a given longitude)
 */
final class SiderealTime
{
    /**
     * Compute Greenwich Mean Sidereal Time (GMST) in degrees [0, 360).
     */
    public static function gmstDegrees(CarbonImmutable $utc): float
    {
        $jd0 = JulianDayCalculator::fromUtc($utc->year, $utc->month, $utc->day, 0, 0, 0);
        $t = JulianDayCalculator::centuriesSinceJ2000($jd0);

        // GMST at 0h UT in degrees (IAU formula)
        $theta0 = 100.46061837 + (36000.770053608 * $t) + (0.000387933 * $t * $t) - (($t * $t * $t) / 38710000.0);

        // Fractional day elapsed in UT
        $fractionalDay = ($utc->hour / 24.0) + ($utc->minute / 1440.0) + ($utc->second / 86400.0) + ($utc->micro / 86_400_000_000.0);

        // Add Earth's rotation
        $gmst = $theta0 + (360.98564736629 * $fractionalDay);

        return SolarLongitude::normalizeDegrees($gmst);
    }

    /**
     * Compute Local Mean Sidereal Time (LMST) in degrees at a given geographic longitude.
     * Longitude East is positive (+), West is negative (-).
     */
    public static function lmstDegrees(CarbonImmutable $utc, float $longitude): float
    {
        return SolarLongitude::normalizeDegrees(self::gmstDegrees($utc) + $longitude);
    }

    /**
     * Convert degrees to hours [0, 24).
     */
    public static function degreesToHours(float $degrees): float
    {
        return SolarLongitude::normalizeDegrees($degrees) / 15.0;
    }
}

