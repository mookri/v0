<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Solar apparent ecliptic longitude (Jean Meeus truncated series).
 * Accuracy: <= 0.01 degrees (inside +/- 15 min fixture tolerance).
 */
final class SolarLongitude
{
    /**
     * @param float $t Julian centuries since J2000.0
     * @return float Solar apparent longitude in degrees [0, 360)
     */
    public static function calculate(float $t): float
    {
        // Geometric mean longitude of the Sun (degrees)
        $l0 = 280.46646 + (36000.76983 * $t) + (0.0003032 * ($t ** 2));
        $l0 = self::normalizeDegrees($l0);

        // Sun's mean anomaly (degrees)
        $m = 357.52911 + (35999.05029 * $t) - (0.0001537 * ($t ** 2));
        $mRad = deg2rad(self::normalizeDegrees($m));

        // Equation of the center C (degrees)
        $c = (1.914602 - (0.004817 * $t) - (0.000014 * ($t ** 2))) * sin($mRad)
            + (0.019993 - (0.000101 * $t)) * sin(2 * $mRad)
            + 0.000289 * sin(3 * $mRad);

        // True solar longitude
        $sunTrue = $l0 + $c;

        // Nutation and aberration correction for apparent longitude
        $omega = 125.04 - (1934.136 * $t);
        $omegaRad = deg2rad(self::normalizeDegrees($omega));

        $lambda = $sunTrue - 0.00569 - (0.00478 * sin($omegaRad));

        return self::normalizeDegrees($lambda);
    }

    public static function normalizeDegrees(float $deg): float
    {
        $d = fmod($deg, 360.0);
        if ($d < 0) {
            $d += 360.0;
        }

        return $d;
    }
}
