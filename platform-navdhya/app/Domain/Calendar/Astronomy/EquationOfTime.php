<?php

namespace App\Domain\Calendar\Astronomy;

use Carbon\CarbonImmutable;

/**
 * Equation of Time (EoT) and True Solar Noon calculator.
 * EoT is the discrepancy between apparent solar time (sun dial) and mean solar time (clock).
 */
final class EquationOfTime
{
    /**
     * Compute Equation of Time in minutes.
     * Positive when apparent solar time is ahead of mean solar time.
     */
    public static function inMinutes(CarbonImmutable $utc): float
    {
        $jd = JulianDayCalculator::fromUtc($utc->year, $utc->month, $utc->day, $utc->hour, $utc->minute, $utc->second);
        $t = JulianDayCalculator::centuriesSinceJ2000($jd);

        // Apparent solar longitude L
        $l = SolarLongitude::calculate($t);

        // Mean solar longitude L0
        $l0 = 280.46646 + (36000.76983 * $t);
        $l0 = SolarLongitude::normalizeDegrees($l0);

        // Sun's right ascension RA
        $lRad = deg2rad($l);
        $ra = rad2deg(atan(0.91764 * tan($lRad)));
        $ra = SolarLongitude::normalizeDegrees($ra);

        // Quadrant adjustment
        $lQuadrant = floor($l / 90.0) * 90.0;
        $raQuadrant = floor($ra / 90.0) * 90.0;
        $ra = $ra + ($lQuadrant - $raQuadrant);

        // Equation of Time in degrees: E = L0 - RA (or apparent - mean)
        $diff = $l0 - $ra;
        // Normalize difference to [-180, 180]
        if ($diff > 180.0) {
            $diff -= 360.0;
        } elseif ($diff < -180.0) {
            $diff += 360.0;
        }

        // 1 degree = 4 minutes of time
        return $diff * 4.0;
    }

    /**
     * Calculate True Solar Noon (Madhyahna central point) in UTC.
     * Solar noon occurs when the Sun crosses the local celestial meridian.
     */
    public static function trueSolarNoonUtc(int $year, int $month, int $day, float $longitude): CarbonImmutable
    {
        $baseNoon = CarbonImmutable::create($year, $month, $day, 12, 0, 0, 'UTC');
        $eotMin = self::inMinutes($baseNoon);

        // Local noon offset from UTC:
        // Longitude East (+): noon occurs EARLIER in UTC (subtract lng/15 hours)
        // EoT (+): apparent noon occurs EARLIER (subtract EoT minutes)
        $longitudeOffsetHours = $longitude / 15.0;
        $totalOffsetSeconds = (int) round(($longitudeOffsetHours * 3600.0) + ($eotMin * 60.0));

        return $baseNoon->subSeconds($totalOffsetSeconds);
    }
}

