<?php

namespace App\Domain\Calendar\Astronomy;

/**
 * Solar-Lunar angular elongation calculation (pure math).
 * Strictly Ayanamsa-invariant (P-04): (Moon - Sun) == ((Moon - A) - (Sun - A)).
 */
final class Elongation
{
    public static function calculate(float $moonLongitude, float $sunLongitude): float
    {
        return SolarLongitude::normalizeDegrees($moonLongitude - $sunLongitude);
    }
}

