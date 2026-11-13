<?php

namespace App\Domain\Jyotish\Ephemeris;

/**
 * Lunar Nodes Engine (T-JY02-02).
 *
 * Implements Mean Node and True (Osculating) Node calculations for Rahu and Ketu.
 * Enforces rigorous 180-degree opposition invariant across all dates and modes.
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class NodesResolver
{
    public const MODE_MEAN = 'mean';
    public const MODE_TRUE = 'true';

    /**
     * Compute Rahu and Ketu longitudes and daily velocities at centuries T from J2000.0.
     *
     * @param float $t Julian centuries since J2000.0
     * @param string $mode 'mean' or 'true'
     * @return array{
     *     rahu: array{longitude: float, latitude: float, distance: float, speed_longitude: float, speed_latitude: float, speed_distance: float},
     *     ketu: array{longitude: float, latitude: float, distance: float, speed_longitude: float, speed_latitude: float, speed_distance: float}
     * }
     */
    public static function resolve(float $t, string $mode = self::MODE_MEAN): array
    {
        $rahuLon = self::calculateRahuLongitude($t, $mode);
        $ketuLon = self::normalizeDegrees($rahuLon + 180.0);

        // Compute numerical rates using delta = 0.0005 days
        $deltaDays = 0.0005;
        $deltaT = $deltaDays / 36525.0;

        $rahuPlus = self::calculateRahuLongitude($t + $deltaT, $mode);
        $rahuMinus = self::calculateRahuLongitude($t - $deltaT, $mode);
        $rahuSpeed = self::angularDifference($rahuPlus, $rahuMinus) / (2.0 * $deltaDays);

        $ketuPlus = self::normalizeDegrees($rahuPlus + 180.0);
        $ketuMinus = self::normalizeDegrees($rahuMinus + 180.0);
        $ketuSpeed = self::angularDifference($ketuPlus, $ketuMinus) / (2.0 * $deltaDays);

        return [
            'rahu' => [
                'longitude' => $rahuLon,
                'latitude' => 0.0,
                'distance' => 1.0,
                'speed_longitude' => $rahuSpeed,
                'speed_latitude' => 0.0,
                'speed_distance' => 0.0,
            ],
            'ketu' => [
                'longitude' => $ketuLon,
                'latitude' => 0.0,
                'distance' => 1.0,
                'speed_longitude' => $ketuSpeed,
                'speed_latitude' => 0.0,
                'speed_distance' => 0.0,
            ],
        ];
    }

    /**
     * Calculate Rahu longitude in degrees [0, 360).
     */
    public static function calculateRahuLongitude(float $t, string $mode = self::MODE_MEAN): float
    {
        // Mean ascending node (Meeus Chapter 47)
        $omega = 125.04452 - (1934.136261 * $t) + (0.0020708 * $t * $t) + (($t * $t * $t) / 450000.0);

        if ($mode === self::MODE_TRUE) {
            // ELP-2000 major osculating perturbations on ascending node
            $d = 297.8501921 + (445267.1114034 * $t);
            $m = 357.5291092 + (35999.0502909 * $t);
            $mp = 134.9633964 + (477198.8675055 * $t);
            $f = 93.2720950 + (483202.0175233 * $t);

            $dRad = deg2rad($d);
            $mRad = deg2rad($m);
            $mpRad = deg2rad($mp);
            $fRad = deg2rad($f);

            $perturbation = (-1.4979 * sin(2.0 * ($dRad - $fRad)))
                + (-0.1500 * sin($mRad))
                + (-0.1226 * sin(2.0 * $dRad))
                + (0.1176 * sin(2.0 * $fRad))
                + (-0.0801 * sin(2.0 * ($mpRad - $fRad)));

            $omega += $perturbation;
        }

        return self::normalizeDegrees($omega);
    }

    public static function normalizeDegrees(float $degrees): float
    {
        $normalized = fmod($degrees, 360.0);
        if ($normalized < 0.0) {
            $normalized += 360.0;
        }

        return $normalized;
    }

    public static function angularDifference(float $a, float $b): float
    {
        $diff = fmod($a - $b + 180.0, 360.0);
        if ($diff < 0.0) {
            $diff += 360.0;
        }

        return $diff - 180.0;
    }
}

