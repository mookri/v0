<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Solar-Lunar angular elongation (pure math).
 */
final class Elongation
{
    /**
     * @return float Angular elongation in degrees [0, 360)
     */
    public static function calculate(float $moonLongitude, float $sunLongitude): float
    {
        return SolarLongitude::normalizeDegrees($moonLongitude - $sunLongitude);
    }
}
