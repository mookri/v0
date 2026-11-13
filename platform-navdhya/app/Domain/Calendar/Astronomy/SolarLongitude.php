<?php

namespace App\Domain\Calendar\Astronomy;

/**
 * Solar celestial longitude calculation (pure math).
 * Truncated Meeus series (VSOP87 derived) delivering ~0.01 degree precision.
 */
final class SolarLongitude
{
    public static function calculate(float $t): float
    {
        // Geometric mean longitude of the Sun
        $l0 = 280.46646 + (36000.76983 * $t) + (0.0003032 * $t * $t);

        // Mean anomaly of the Sun
        $m = 357.52911 + (35999.05029 * $t) - (0.0001537 * $t * $t);
        $mRad = deg2rad($m);

        // Sun equation of center C
        $c = ((1.914602 - (0.004817 * $t) - (0.000014 * $t * $t)) * sin($mRad))
            + ((0.019993 - (0.000101 * $t)) * sin(2 * $mRad))
            + (0.000289 * sin(3 * $mRad));

        // True solar longitude
        $trueLon = $l0 + $c;

        // Apparent solar longitude (corrected for aberration and nutation)
        $apparentLon = $trueLon - 0.00569 - (0.00478 * sin(deg2rad(125.04 - (1934.136 * $t))));

        return self::normalizeDegrees($apparentLon);
    }

    public static function normalizeDegrees(float $degrees): float
    {
        $normalized = fmod($degrees, 360.0);
        if ($normalized < 0.0) {
            $normalized += 360.0;
        }

        return $normalized;
    }
}

