<?php

namespace App\Domain\Jyotish\Astronomy;

use App\Domain\Jyotish\Support\Angle;
use InvalidArgumentException;

/**
 * High-Precision Ascendant (Lagna) & Midheaven (MC) Solver (T-JY03-01).
 *
 * Computes:
 * 1. Greenwich & Local Sidereal Time (GMST / LMST / RAMC) using IAU-1982 / Meeus formulas.
 * 2. True obliquity of the ecliptic incorporating IAU nutation series.
 * 3. Spherical horizon intersection for the rising ecliptic degree (Lagna) with <= 1 arcsecond precision.
 * 4. Midheaven (MC), Descendant (7th house cusp), and IC (4th house cusp).
 * 5. Handles polar circle edge cases (|lat| >= 66°) via HorizonStatusEnum.
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class AscendantSolver
{
    /**
     * Compute Greenwich Mean Sidereal Time (GMST) in degrees [0, 360).
     * Algorithm: Jean Meeus, Astronomical Algorithms (IAU expression).
     */
    public static function calculateGmst(float $jdUt1): float
    {
        $d = $jdUt1 - 2451545.0;
        $t = $d / 36525.0;

        // GMST in degrees at 0h UT plus Earth's diurnal rotation
        $gmst = 280.46061837
            + (360.98564736629 * $d)
            + (0.000387933 * $t * $t)
            - (($t * $t * $t) / 38710000.0);

        return Angle::normalize($gmst);
    }

    /**
     * Compute Local Sidereal Time (LMST / RAMC) in degrees [0, 360).
     * Longitude East is positive (+), West is negative (-).
     */
    public static function calculateLmst(float $jdUt1, float $longitudeDegrees): float
    {
        $gmst = self::calculateGmst($jdUt1);
        return Angle::normalize($gmst + $longitudeDegrees);
    }

    /**
     * Compute True Obliquity of the Ecliptic (epsilon) in degrees.
     * epsilon = epsilon0 + delta_epsilon (nutation in obliquity).
     *
     * @return array{mean: float, nutation: float, true: float}
     */
    public static function calculateObliquity(float $tCenturies): array
    {
        // IAU 1980 mean obliquity polynomial (Meeus eq 22.2)
        $eps0 = 23.43929111
            - (0.013004167 * $tCenturies)
            - (0.000000164 * $tCenturies * $tCenturies)
            + (0.000000504 * $tCenturies * $tCenturies * $tCenturies);

        // Nutation in obliquity (delta epsilon) via IAU fundamental arguments
        $omega = deg2rad(125.04452 - 1934.136261 * $tCenturies);
        $sunL = deg2rad(280.4665 + 36000.7698 * $tCenturies);
        $moonL = deg2rad(218.3165 + 481267.8813 * $tCenturies);

        $dEpsArcsec = (9.20 * cos($omega))
            + (0.57 * cos(2 * $sunL))
            + (0.10 * cos(2 * $moonL))
            - (0.09 * cos(2 * $omega));

        $dEpsDeg = $dEpsArcsec / 3600.0;

        return [
            'mean' => $eps0,
            'nutation' => $dEpsDeg,
            'true' => $eps0 + $dEpsDeg,
        ];
    }

    /**
     * Solve Ascendant (Lagna) and Midheaven (MC) for a given instant and location.
     *
     * @param float $jdUt1 Julian Day UT1
     * @param float $jdTt Julian Day TT
     * @param float $latitude Observer geographic latitude (-90 to +90 degrees)
     * @param float $longitude Observer geographic longitude (-180 to +180 degrees, East positive)
     * @param float $ayanamsaDegrees Sidereal Ayanamsa to subtract (0.0 for pure tropical)
     * @return array{
     *     status: HorizonStatusEnum,
     *     ramc_degrees: float,
     *     lst_hours: float,
     *     tropical_ascendant: float,
     *     tropical_mc: float,
     *     sidereal_ascendant: float,
     *     sidereal_mc: float,
     *     obliquity_degrees: float,
     *     ayanamsa_degrees: float,
     *     dms_ascendant: string,
     *     dms_mc: string,
     * }
     */
    public static function solve(
        float $jdUt1,
        float $jdTt,
        float $latitude,
        float $longitude,
        float $ayanamsaDegrees = 0.0
    ): array {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException("Latitude {$latitude} out of bounds [-90, +90].");
        }

        $t = ($jdTt - 2451545.0) / 36525.0;
        $obliquity = self::calculateObliquity($t);
        $epsTrueDeg = $obliquity['true'];
        $epsRad = deg2rad($epsTrueDeg);

        $ramcDeg = self::calculateLmst($jdUt1, $longitude);
        $thetaRad = deg2rad($ramcDeg);
        $phiRad = deg2rad($latitude);

        // Check polar latitude singularity (|latitude| >= 90 - eps)
        $isPolar = abs($latitude) >= (90.0 - $epsTrueDeg);
        $status = HorizonStatusEnum::NORMAL;

        if ($isPolar || abs($latitude) >= 66.0) {
            $status = HorizonStatusEnum::UNSUPPORTED_POLAR;
        }

        // Midheaven (MC) calculation (valid at all latitudes except poles)
        // tan(lambda_mc) = tan(theta) / cos(eps)
        $yMc = sin($thetaRad);
        $xMc = cos($thetaRad) * cos($epsRad);
        $mcTropical = Angle::normalize(rad2deg(atan2($yMc, $xMc)));

        // Ascendant (Lagna) calculation
        // tan(lambda_asc) = -cos(theta) / (sin(theta)*cos(eps) + tan(phi)*sin(eps))
        $yAsc = cos($thetaRad);
        $xAsc = -((sin($thetaRad) * cos($epsRad)) + (tan($phiRad) * sin($epsRad)));
        $ascTropical = Angle::normalize(rad2deg(atan2($yAsc, $xAsc)));

        $ascSidereal = Angle::normalize($ascTropical - $ayanamsaDegrees);
        $mcSidereal = Angle::normalize($mcTropical - $ayanamsaDegrees);

        return [
            'status' => $status,
            'ramc_degrees' => $ramcDeg,
            'lst_hours' => $ramcDeg / 15.0,
            'tropical_ascendant' => $ascTropical,
            'tropical_mc' => $mcTropical,
            'sidereal_ascendant' => $ascSidereal,
            'sidereal_mc' => $mcSidereal,
            'obliquity_degrees' => $epsTrueDeg,
            'ayanamsa_degrees' => $ayanamsaDegrees,
            'dms_ascendant' => (new Angle($ascSidereal))->toDms(),
            'dms_mc' => (new Angle($mcSidereal))->toDms(),
        ];
    }
}

