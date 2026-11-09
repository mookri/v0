<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Civil sunrise time calculation in UTC (pure math).
 * Standard atmospheric refraction + solar semi-diameter zenith: 90°50' (90.8333°).
 */
final class SunriseUtcCalculator
{
    public const ZENITH = 90.8333; // 90 degrees 50 minutes

    /**
     * @param int $year Gregorian year
     * @param int $month Gregorian month
     * @param int $day Gregorian day
     * @param float $latitude Degrees North (+), South (-)
     * @param float $longitude Degrees East (+), West (-)
     * @return array{hour: int, minute: int, second: int, fractional_hour: float} UTC sunrise time
     */
    public static function calculate(int $year, int $month, int $day, float $latitude = 23.1765, float $longitude = 75.7885): array
    {
        // Day of the year N
        $n1 = (int) floor(275 * $month / 9);
        $n2 = (int) floor(($month + 9) / 12);
        $n3 = (1 + (int) floor(($year - 4 * (int) floor($year / 4) + 2) / 3));
        $n = $n1 - ($n2 * $n3) + $day - 30;

        // Approximate time of sunrise
        $lngHour = $longitude / 15.0;
        $t = $n + ((6.0 - $lngHour) / 24.0);

        // Sun's mean anomaly M
        $m = (0.9856 * $t) - 3.289;
        $mRad = deg2rad($m);

        // Sun's true longitude L
        $l = $m + (1.916 * sin($mRad)) + (0.020 * sin(2 * $mRad)) + 282.634;
        $l = SolarLongitude::normalizeDegrees($l);
        $lRad = deg2rad($l);

        // Sun's right ascension RA
        $ra = rad2deg(atan(0.91764 * tan($lRad)));
        $ra = SolarLongitude::normalizeDegrees($ra);

        // Right ascension quadrant adjustment
        $lQuadrant = floor($l / 90.0) * 90.0;
        $raQuadrant = floor($ra / 90.0) * 90.0;
        $ra = $ra + ($lQuadrant - $raQuadrant);
        $raHour = $ra / 15.0;

        // Sun's declination sinDec and cosDec
        $sinDec = 0.39782 * sin($lRad);
        $cosDec = cos(asin($sinDec));

        // Sun's local hour angle cosH
        $cosH = (cos(deg2rad(self::ZENITH)) - ($sinDec * sin(deg2rad($latitude)))) / ($cosDec * cos(deg2rad($latitude)));

        if ($cosH > 1.0) {
            // Sun never rises (polar night) -> default to 06:00
            $h = 0.0;
        } elseif ($cosH < -1.0) {
            // Sun never sets (midnight sun) -> default to 00:00
            $h = 0.0;
        } else {
            $h = 360.0 - rad2deg(acos($cosH));
        }

        $hHour = $h / 15.0;

        // Local mean time of sunrise
        $localMeanTime = $hHour + $raHour - (0.06571 * $t) - 6.622;

        // Universal Time (UTC)
        $ut = $localMeanTime - $lngHour;
        $ut = fmod($ut, 24.0);
        if ($ut < 0) {
            $ut += 24.0;
        }

        $hour = (int) floor($ut);
        $remainderMin = ($ut - $hour) * 60.0;
        $minute = (int) floor($remainderMin);
        $second = (int) round(($remainderMin - $minute) * 60.0);
        if ($second >= 60) {
            $second = 0;
            $minute += 1;
        }
        if ($minute >= 60) {
            $minute = 0;
            $hour = ($hour + 1) % 24;
        }

        return [
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second,
            'fractional_hour' => $ut,
        ];
    }
}
