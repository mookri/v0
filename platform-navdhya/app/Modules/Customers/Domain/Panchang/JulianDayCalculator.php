<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Astronomical Julian Day Number calculation (pure math).
 * J2000 epoch: 2000-01-01 12:00:00 UTC = JD 2451545.0.
 */
final class JulianDayCalculator
{
    public static function fromUtc(int $year, int $month, int $day, int $hour = 0, int $minute = 0, int $second = 0): float
    {
        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }

        $a = (int) floor($year / 100);
        $b = 2 - $a + (int) floor($a / 4);

        $dayFraction = ($hour + ($minute / 60.0) + ($second / 3600.0)) / 24.0;

        return floor(365.25 * ($year + 4716))
            + floor(30.6001 * ($month + 1))
            + $day
            + $dayFraction
            + $b
            - 1524.5;
    }

    public static function centuriesSinceJ2000(float $jd): float
    {
        return ($jd - 2451545.0) / 36525.0;
    }
}
