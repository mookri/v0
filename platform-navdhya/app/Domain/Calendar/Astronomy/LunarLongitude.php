<?php

namespace App\Domain\Calendar\Astronomy;

/**
 * Lunar celestial longitude calculation (pure math).
 * Truncated Meeus ELP-2000 series delivering ~0.05 degree precision.
 */
final class LunarLongitude
{
    public static function calculate(float $t): float
    {
        // Moon's mean longitude L'
        $lp = 218.3164477 + (481267.88123421 * $t) - (0.0015786 * $t * $t);

        // Moon's mean elongation D
        $d = 297.8501921 + (445267.1114034 * $t) - (0.0018819 * $t * $t);

        // Sun's mean anomaly M
        $m = 357.5291092 + (35999.0502909 * $t) - (0.0001536 * $t * $t);

        // Moon's mean anomaly M'
        $mp = 134.9633964 + (477198.8675055 * $t) + (0.0087414 * $t * $t);

        // Moon's argument of latitude F
        $f = 93.2720950 + (483202.0175233 * $t) - (0.0036539 * $t * $t);

        // Convert key angles to radians
        $dRad = deg2rad($d);
        $mRad = deg2rad($m);
        $mpRad = deg2rad($mp);
        $fRad = deg2rad($f);

        // Periodic perturbation terms for longitude (degrees)
        $sigmaL = (6.288774 * sin($mpRad))
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
            - (0.012528 * sin($mpRad + (2 * $fRad)))
            + (0.010980 * sin($mpRad - (2 * $fRad)));

        $apparentMoonLon = $lp + $sigmaL;

        return SolarLongitude::normalizeDegrees($apparentMoonLon);
    }
}

