<?php

namespace App\Domain\Calendar\Astronomy;

use Carbon\CarbonImmutable;

/**
 * Civil sunset time calculation in UTC (pure math).
 * Standard atmospheric refraction + solar semi-diameter zenith: 90°50' (90.8333°).
 *
 * Implements polar refusal (P-05) and exact microsecond timeline rollover (P-06).
 */
final class SunsetUtcCalculator
{
    public const ZENITH = 90.8333; // 90 degrees 50 minutes

    /**
     * @param int $year Gregorian year
     * @param int $month Gregorian month
     * @param int $day Gregorian day
     * @param float $latitude Degrees North (+), South (-)
     * @param float $longitude Degrees East (+), West (-)
     */
    public static function calculate(int $year, int $month, int $day, float $latitude = 23.1765, float $longitude = 75.7885): HorizonResult
    {
        if (abs($latitude) > 90.0) {
            return HorizonResult::unsupportedLatitude();
        }

        // Day of the year N
        $n1 = (int) floor(275 * $month / 9);
        $n2 = (int) floor(($month + 9) / 12);
        $n3 = (1 + (int) floor(($year - 4 * (int) floor($year / 4) + 2) / 3));
        $n = $n1 - ($n2 * $n3) + $day - 30;

        // Approximate time of sunset (18h local)
        $lngHour = $longitude / 15.0;
        $t = $n + ((18.0 - $lngHour) / 24.0);

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
        $denom = $cosDec * cos(deg2rad($latitude));
        if (abs($denom) < 1e-12) {
            return HorizonResult::unsupportedLatitude();
        }
        $cosH = (cos(deg2rad(self::ZENITH)) - ($sinDec * sin(deg2rad($latitude)))) / $denom;

        // Polar checks (P-05)
        if ($cosH > 1.0) {
            return HorizonResult::polarNight();
        }

        if ($cosH < -1.0) {
            return HorizonResult::midnightSun();
        }

        // Sunset uses H directly
        $h = rad2deg(acos($cosH));
        $hHour = $h / 15.0;

        // Local mean time of sunset
        $localMeanTime = $hHour + $raHour - (0.06571 * $t) - 6.622;
        $localMeanTime = fmod($localMeanTime, 24.0);
        if ($localMeanTime < 0) {
            $localMeanTime += 24.0;
        }

        // Universal Time (UTC) relative to local day
        $ut = $localMeanTime - $lngHour;

        // Microsecond timeline arithmetic
        $baseDate = CarbonImmutable::create($year, $month, $day, 0, 0, 0, 'UTC');
        $totalMicroseconds = (int) round($ut * 3600.0 * 1_000_000.0);
        $instantUtc = $baseDate->addMicroseconds($totalMicroseconds);

        $hour = (int) $instantUtc->format('H');
        $minute = (int) $instantUtc->format('i');
        $second = (int) $instantUtc->format('s');
        $fractionalHour = $hour + ($minute / 60.0) + ($second / 3600.0) + ($instantUtc->micro / 3_600_000_000.0);

        return HorizonResult::normal(
            instantUtc: $instantUtc,
            hour: $hour,
            minute: $minute,
            second: $second,
            fractionalHour: $fractionalHour
        );
    }
}

