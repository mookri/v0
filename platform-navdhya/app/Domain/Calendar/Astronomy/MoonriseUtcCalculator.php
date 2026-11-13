<?php

namespace App\Domain\Calendar\Astronomy;

use Carbon\CarbonImmutable;

/**
 * Topocentric Moonrise and Moonset calculator (pure math).
 *
 * Accounts for:
 * - Lunar horizontal parallax (~57' = 0.95°)
 * - Atmospheric refraction (~34' = 0.5667°)
 * - Lunar semi-diameter (~16' = 0.2667°)
 *
 * Effective zenith at horizon:
 * Z = 90° + refraction + semi-diameter - parallax ≈ 89.8833°
 */
final class MoonriseUtcCalculator
{
    public const MOON_ZENITH = 89.8833;

    /**
     * Calculate UTC moonrise and moonset for a given date and geographic coordinates.
     *
     * @return array{
     *     moonrise: ?CarbonImmutable,
     *     moonset: ?CarbonImmutable,
     *     has_moonrise: bool,
     *     has_moonset: bool
     * }
     */
    public static function calculate(int $year, int $month, int $day, float $latitude, float $longitude): array
    {
        $baseDate = CarbonImmutable::create($year, $month, $day, 0, 0, 0, 'UTC');

        // Sample Moon altitude at 1-hour intervals across 24 hours
        $hours = [];
        for ($h = 0; $h <= 24; $h++) {
            $tInstant = $baseDate->addHours($h);
            $jd = JulianDayCalculator::fromUtc($year, $month, $day, $h, 0, 0);
            $t = JulianDayCalculator::centuriesSinceJ2000($jd);

            $moonLon = LunarLongitude::calculate($t);
            $moonDec = rad2deg(asin(0.39782 * sin(deg2rad($moonLon)))); // Approximate declination

            $lmst = SiderealTime::lmstDegrees($tInstant, $longitude);
            $hourAngle = SolarLongitude::normalizeDegrees($lmst - $moonLon);
            if ($hourAngle > 180.0) {
                $hourAngle -= 360.0;
            }

            // Altitude in degrees
            $sinAlt = (sin(deg2rad($latitude)) * sin(deg2rad($moonDec)))
                + (cos(deg2rad($latitude)) * cos(deg2rad($moonDec)) * cos(deg2rad($hourAngle)));
            $alt = rad2deg(asin(max(-1.0, min(1.0, $sinAlt))));

            $hours[$h] = $alt - (90.0 - self::MOON_ZENITH);
        }

        $moonrise = null;
        $moonset = null;

        for ($h = 0; $h < 24; $h++) {
            $y1 = $hours[$h];
            $y2 = $hours[$h + 1];

            // Rising: goes from below horizon to above horizon
            if ($y1 < 0.0 && $y2 >= 0.0 && $moonrise === null) {
                $fraction = - $y1 / ($y2 - $y1);
                $seconds = (int) round(($h + $fraction) * 3600.0);
                $moonrise = $baseDate->addSeconds($seconds);
            }

            // Setting: goes from above horizon to below horizon
            if ($y1 >= 0.0 && $y2 < 0.0 && $moonset === null) {
                $fraction = $y1 / ($y1 - $y2);
                $seconds = (int) round(($h + $fraction) * 3600.0);
                $moonset = $baseDate->addSeconds($seconds);
            }
        }

        return [
            'moonrise' => $moonrise,
            'moonset' => $moonset,
            'has_moonrise' => $moonrise !== null,
            'has_moonset' => $moonset !== null,
        ];
    }
}

