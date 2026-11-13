<?php

namespace App\Domain\Calendar\Astronomy;

/**
 * Julian Day calculation from Gregorian calendar date and UTC time (pure math).
 * Algorithm from Meeus Astronomical Algorithms, Chapter 7.
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

        $fractionalDay = $day + ($hour / 24.0) + ($minute / 1440.0) + ($second / 86400.0);

        return floor(365.25 * ($year + 4716))
            + floor(30.6001 * ($month + 1))
            + $fractionalDay
            + $b
            - 1524.5;
    }

    public static function centuriesSinceJ2000(float $jd): float
    {
        return ($jd - 2451545.0) / 36525.0;
    }
}

