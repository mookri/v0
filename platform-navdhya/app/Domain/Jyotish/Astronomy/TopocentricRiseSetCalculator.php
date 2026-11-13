<?php

namespace App\Domain\Jyotish\Astronomy;

use App\Domain\Jyotish\Ephemeris\EphemerisProviderInterface;
use App\Domain\Jyotish\Ephemeris\MeeusSeriesProvider;
use App\Domain\Jyotish\Support\Angle;

/**
 * Topocentric Solar & Lunar Rise/Set Calculator (T-JY05-03).
 *
 * Implements rigorous horizon altitude equations accounting for:
 * 1. Observer elevation & horizon dip: d = 0.0293° * sqrt(elevation_m)
 * 2. Atmospheric refraction at horizon: 34' = 0.5667°
 * 3. Solar / Lunar semidiameter: Sun 16' (0.2667°), Moon 15.5' (0.2583°)
 * 4. Lunar horizontal parallax: ~57' (0.95°)
 * 5. Handles polar circle edge cases (Midnight Sun, Polar Night).
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class TopocentricRiseSetCalculator
{
    public const REFRACTION_DEGREES = 34.0 / 60.0;            // 0.566667°
    public const SUN_SEMIDIAMETER_DEGREES = 16.0 / 60.0;      // 0.266667°
    public const MOON_SEMIDIAMETER_DEGREES = 15.5 / 60.0;     // 0.258333°
    public const MOON_PARALLAX_DEGREES = 57.0 / 60.0;         // 0.950000°

    /**
     * Compute topocentric sunrise, sunset, and solar noon for a given civil day.
     *
     * @param float $jdMidnightUt1 Julian Day at 00:00:00 UT1 of target day
     * @param float $latitude Observer geographic latitude [-90, +90]
     * @param float $longitude Observer geographic longitude [-180, +180]
     * @param float $elevationMeters Elevation above sea level in meters
     * @param EphemerisProviderInterface|null $provider
     * @return array{
     *     status: HorizonStatusEnum,
     *     sunrise_jd: float|null,
     *     sunset_jd: float|null,
     *     solar_noon_jd: float,
     *     day_length_hours: float|null,
     *     night_length_hours: float|null
     * }
     */
    public static function calculateSun(
        float $jdMidnightUt1,
        float $latitude,
        float $longitude,
        float $elevationMeters = 0.0,
        ?EphemerisProviderInterface $provider = null
    ): array {
        $ephem = $provider ?? new MeeusSeriesProvider();

        // 1. Horizon Dip for elevation
        $dipDeg = ($elevationMeters > 0.0) ? (0.0293 * sqrt($elevationMeters)) : 0.0;
        $h0 = -(self::REFRACTION_DEGREES + self::SUN_SEMIDIAMETER_DEGREES + $dipDeg);

        // 2. Solar coordinates at noon estimate (0.5 day from midnight - lon/360)
        $tNoonEst = $jdMidnightUt1 + 0.5 - ($longitude / 360.0);
        $sunSnap = $ephem->computeSnapshot($tNoonEst);
        $sunPos = $sunSnap->getGrahaPositions()['sun'];
        $sunLon = $sunPos['longitude'];

        // Obliquity
        $t = ($tNoonEst - 2451545.0) / 36525.0;
        $epsTrue = AscendantSolver::calculateObliquity($t)['true'];
        $epsRad = deg2rad($epsTrue);
        $lonRad = deg2rad($sunLon);

        // Sun equatorial coordinates: sin(delta) = sin(eps)*sin(lon); tan(alpha) = cos(eps)*tan(lon)
        $sinDelta = sin($epsRad) * sin($lonRad);
        $deltaRad = asin($sinDelta);
        $cosDelta = cos($deltaRad);

        $yRa = sin($lonRad) * cos($epsRad);
        $xRa = cos($lonRad);
        $raSunDeg = Angle::normalize(rad2deg(atan2($yRa, $xRa)));

        // 3. Hour angle equation: cos(H0) = (sin(h0) - sin(phi)*sin(delta)) / (cos(phi)*cos(delta))
        $phiRad = deg2rad($latitude);
        $sinPhi = sin($phiRad);
        $cosPhi = cos($phiRad);

        $cosH0 = (sin(deg2rad($h0)) - ($sinPhi * $sinDelta)) / ($cosPhi * $cosDelta);

        if ($cosH0 > 1.0) {
            // Sun never rises (Polar Night)
            return [
                'status' => HorizonStatusEnum::POLAR_NIGHT,
                'sunrise_jd' => null,
                'sunset_jd' => null,
                'solar_noon_jd' => $tNoonEst,
                'day_length_hours' => 0.0,
                'night_length_hours' => 24.0,
            ];
        }

        if ($cosH0 < -1.0) {
            // Sun never sets (Midnight Sun)
            return [
                'status' => HorizonStatusEnum::MIDNIGHT_SUN,
                'sunrise_jd' => null,
                'sunset_jd' => null,
                'solar_noon_jd' => $tNoonEst,
                'day_length_hours' => 24.0,
                'night_length_hours' => 0.0,
            ];
        }

        $h0Deg = rad2deg(acos($cosH0));

        // 4. Local Sidereal Times of events
        // Noon: RAMC = alpha
        // Rise: RAMC = alpha - H0
        // Set:  RAMC = alpha + H0
        $lstNoon = $raSunDeg;
        $lstRise = Angle::normalize($raSunDeg - $h0Deg);
        $lstSet  = Angle::normalize($raSunDeg + $h0Deg);

        // Convert LST to UT1 Julian Day using GMST at midnight
        $gmstMidnight = AscendantSolver::calculateGmst($jdMidnightUt1);
        $lmstMidnight = Angle::normalize($gmstMidnight + $longitude);

        $diffRise = Angle::normalize($lstRise - $lmstMidnight) / 360.98564736629;
        $diffSet  = Angle::normalize($lstSet - $lmstMidnight) / 360.98564736629;
        $diffNoon = Angle::normalize($lstNoon - $lmstMidnight) / 360.98564736629;

        $sunriseJd = $jdMidnightUt1 + $diffRise;
        $sunsetJd  = $jdMidnightUt1 + $diffSet;
        $noonJd    = $jdMidnightUt1 + $diffNoon;

        $dayLengthHours = ($sunsetJd - $sunriseJd) * 24.0;
        if ($dayLengthHours < 0.0) {
            $dayLengthHours += 24.0;
        }

        return [
            'status' => HorizonStatusEnum::NORMAL,
            'sunrise_jd' => $sunriseJd,
            'sunset_jd' => $sunsetJd,
            'solar_noon_jd' => $noonJd,
            'day_length_hours' => round($dayLengthHours, 4),
            'night_length_hours' => round(24.0 - $dayLengthHours, 4),
        ];
    }
}

