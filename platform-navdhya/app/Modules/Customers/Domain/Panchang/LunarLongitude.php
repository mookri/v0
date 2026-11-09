<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Lunar apparent ecliptic longitude (Jean Meeus truncated periodic terms).
 * Includes principal perturbations: Evection, Variation, Equation of Center.
 * Accuracy: <= 0.02 degrees.
 */
final class LunarLongitude
{
    /**
     * @param float $t Julian centuries since J2000.0
     * @return float Lunar apparent ecliptic longitude in degrees [0, 360)
     */
    public static function calculate(float $t): float
    {
        // Moon's mean longitude L'
        $lp = 218.3164477 + (481267.88123421 * $t) - (0.0015786 * ($t ** 2));
        $lp = SolarLongitude::normalizeDegrees($lp);

        // Moon's mean elongation D
        $d = 297.8501921 + (445267.1114034 * $t) - (0.0018819 * ($t ** 2));
        $d = SolarLongitude::normalizeDegrees($d);
        $dRad = deg2rad($d);

        // Sun's mean anomaly M
        $m = 357.5291092 + (35999.0502909 * $t) - (0.0001536 * ($t ** 2));
        $m = SolarLongitude::normalizeDegrees($m);
        $mRad = deg2rad($m);

        // Moon's mean anomaly M'
        $mp = 134.9633964 + (477198.8675055 * $t) + (0.0087414 * ($t ** 2));
        $mp = SolarLongitude::normalizeDegrees($mp);
        $mpRad = deg2rad($mp);

        // Moon's argument of latitude F
        $f = 93.2720950 + (483202.0175233 * $t) - (0.0036539 * ($t ** 2));
        $f = SolarLongitude::normalizeDegrees($f);
        $fRad = deg2rad($f);

        // Principal periodic perturbations in longitude (degrees)
        $sumL = (6.288774 * sin($mpRad))
            + (1.274027 * sin((2 * $dRad) - $mpRad))
            + (0.658314 * sin(2 * $dRad))
            + (0.213618 * sin(2 * $mpRad))
            - (0.185116 * sin($mRad))
            - (0.114332 * sin(2 * $fRad))
            + (0.058793 * sin((2 * $dRad) - (2 * $mpRad)))
            + (0.057066 * sin((2 * $dRad) - $mRad - $mpRad))
            + (0.053322 * sin((2 * $dRad) + $mpRad))
            + (0.045758 * sin((2 * $dRad) - $mRad))
            - (0.040923 * sin($mRad - $mpRad))
            - (0.034720 * sin($dRad))
            - (0.030383 * sin($mRad + $mpRad))
            + (0.015327 * sin((2 * $dRad) - (2 * $fRad)))
            - (0.012528 * sin((2 * $dRad) + $mRad - $mpRad))
            + (0.010980 * sin((2 * $dRad) - $mRad + $mpRad))
            + (0.010675 * sin((4 * $dRad) - $mpRad))
            + (0.010034 * sin(3 * $mpRad));

        // Nutation correction
        $omega = 125.04 - (1934.136 * $t);
        $omegaRad = deg2rad(SolarLongitude::normalizeDegrees($omega));
        $nutation = -0.00478 * sin($omegaRad);

        $lambdaMoon = $lp + $sumL + $nutation;

        return SolarLongitude::normalizeDegrees($lambdaMoon);
    }
}
