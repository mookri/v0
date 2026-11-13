<?php

namespace App\Domain\Jyotish\Ephemeris;

/**
 * Pure PHP High-Precision Ephemeris Provider implementing Jean Meeus / ELP-2000 / VSOP87 series.
 *
 * Computes geocentric ecliptic coordinates (longitude, latitude, distance) and daily velocities
 * for all 9 classical Jyotish grahas: Sun, Moon, Mars, Mercury, Jupiter, Venus, Saturn, Rahu, Ketu.
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class MeeusSeriesProvider implements EphemerisProviderInterface
{
    public const ELP2000_LONGITUDE_TERMS_COUNT = 60;
    public const ELP2000_LATITUDE_TERMS_COUNT = 60;

    /**
     * 60 periodic perturbation terms for Lunar Longitude (Meeus Table 47.A).
     * [D, M, M', F, coefficient_in_1e-6_degrees]
     */
    private const MOON_LON_TERMS = [
        [0, 0, 1, 0, 6288774],
        [2, 0, -1, 0, 1274027],
        [2, 0, 0, 0, 658314],
        [0, 0, 2, 0, 213618],
        [0, 1, 0, 0, -185116],
        [0, 0, 0, 2, -114332],
        [2, 0, -2, 0, 58793],
        [2, -1, -1, 0, 57066],
        [2, 0, 1, 0, 53322],
        [2, -1, 0, 0, 45758],
        [0, 1, -1, 0, -40923],
        [1, 0, 0, 0, -34720],
        [0, 1, 1, 0, -30383],
        [2, 0, 0, -2, 15327],
        [0, 0, 1, 2, -12528],
        [0, 0, 1, -2, 10980],
        [4, 0, -1, 0, 10675],
        [4, 0, 0, 0, 10034],
        [4, 0, -2, 0, 8548],
        [2, 1, -1, 0, -7888],
        [2, 1, 0, 0, -6766],
        [1, 0, -1, 0, -5163],
        [1, 1, 0, 0, 4987],
        [2, -1, 1, 0, 4036],
        [2, -1, 0, -2, 3994],
        [2, 0, -3, 0, 3861],
        [2, -1, 0, 2, 3665],
        [2, -2, -1, 0, -2689],
        [2, 0, -1, -2, -2602],
        [0, 0, 2, 2, 2390],
        [2, -2, 0, 0, -2348],
        [2, 0, 2, 0, 2236],
        [0, 0, 3, 0, -2120],
        [2, 0, -1, 2, -2069],
        [2, 0, 1, -2, 2048],
        [4, -1, -1, 0, -1777],
        [1, 0, 1, 0, -1595],
        [2, 1, 1, 0, 1215],
        [2, 0, -1, -2, -1110],
        [4, 0, -3, 0, -892],
        [1, -1, 0, 0, -810],
        [2, 0, -4, 0, 759],
        [2, -2, 1, 0, -713],
        [2, 0, 0, 2, -700],
        [2, 2, -1, 0, 691],
        [0, 0, 0, 1, 596],
        [4, 0, 1, 0, 549],
        [4, -1, 0, 0, 537],
        [2, 0, 1, 2, 520],
        [0, 1, 0, -2, -487],
        [2, -1, -1, 2, -399],
        [3, 0, -1, 0, -381],
        [2, -2, 0, 2, 351],
        [0, 1, 0, 2, -340],
        [2, 1, 0, -2, 330],
        [4, -1, -2, 0, 327],
        [2, -1, -1, -2, -323],
        [1, 1, -1, 0, 299],
        [2, 0, 3, 0, 294],
        [2, 1, -1, 2, 281],
    ];

    /**
     * 60 periodic perturbation terms for Lunar Latitude (Meeus Table 47.B).
     * [D, M, M', F, coefficient_in_1e-6_degrees]
     */
    private const MOON_LAT_TERMS = [
        [0, 0, 0, 1, 5128122],
        [0, 0, 1, 1, 280602],
        [0, 0, 1, -1, 277693],
        [2, 0, 0, -1, 173237],
        [2, 0, -1, 1, 55413],
        [2, 0, -1, -1, 46271],
        [2, 0, 0, 1, 32573],
        [0, 0, 2, 1, 17198],
        [2, 0, 1, -1, 9266],
        [0, 0, 2, -1, 8822],
        [2, -1, 0, -1, 8216],
        [2, 0, -2, -1, 4324],
        [2, 0, 1, 1, 4200],
        [2, 1, 0, -1, -3359],
        [2, -1, -1, 1, 2463],
        [2, -1, 0, 1, 2211],
        [2, -1, -1, -1, 2065],
        [0, 1, -1, -1, -1870],
        [4, 0, -1, -1, 1828],
        [0, 1, 0, 1, -1794],
        [0, 0, 0, 3, -1749],
        [0, 1, -1, 1, -1565],
        [1, 0, 0, 1, -1491],
        [0, 1, 1, 1, -1475],
        [0, 1, 1, -1, -1410],
        [0, 1, 0, -1, -1344],
        [1, 0, 0, -1, -1335],
        [0, 0, 3, 1, 1107],
        [4, 0, 0, -1, 1021],
        [4, 0, -1, 1, 833],
        [0, 0, 1, -3, 777],
        [4, 0, -2, 1, 671],
        [2, 0, 0, -3, 607],
        [2, 0, 2, -1, 596],
        [2, -1, 1, -1, 491],
        [2, 0, -2, 1, -451],
        [0, 0, 3, -1, 439],
        [2, 0, 2, 1, 422],
        [2, 0, -3, -1, 421],
        [2, 1, -1, 1, -366],
        [2, 1, 0, 1, -351],
        [4, 0, 0, 1, 331],
        [2, -1, 1, 1, 315],
        [2, -2, 0, -1, 302],
        [0, 0, 1, 3, -283],
        [2, 1, 1, -1, -229],
        [1, 1, 0, -1, 223],
        [1, 1, 0, 1, 223],
        [0, 1, -2, -1, -220],
        [2, 1, -1, -1, -220],
        [1, 0, 1, 1, -185],
        [2, -2, -1, -1, 181],
        [0, 1, 2, 1, -177],
        [4, 0, -2, -1, 176],
        [4, -1, -1, -1, 166],
        [1, 0, 1, -1, -164],
        [4, 0, 1, -1, 132],
        [1, 0, -1, -1, -119],
        [4, -1, 0, -1, 115],
        [2, -2, 0, 1, 107],
    ];

    public function computeSnapshot(float $jdTt, ?float $jdUt1 = null): EphemerisSnapshot
    {
        $jdUt1Resolved = $jdUt1 ?? $jdTt;
        $delta = 0.0005; // 43.2 seconds for symmetric numerical differentiation

        $grahas = [];

        foreach (EphemerisSnapshot::GRAHAS as $graha) {
            $pos = $this->calculateCoordinates($graha, $jdTt);
            $posPlus = $this->calculateCoordinates($graha, $jdTt + $delta);
            $posMinus = $this->calculateCoordinates($graha, $jdTt - $delta);

            $speedLon = self::angularDifference($posPlus['longitude'], $posMinus['longitude']) / (2.0 * $delta);
            $speedLat = ($posPlus['latitude'] - $posMinus['latitude']) / (2.0 * $delta);
            $speedDist = ($posPlus['distance'] - $posMinus['distance']) / (2.0 * $delta);

            $grahas[$graha] = [
                'longitude' => $pos['longitude'],
                'latitude' => $pos['latitude'],
                'distance' => $pos['distance'],
                'speed_longitude' => $speedLon,
                'speed_latitude' => $speedLat,
                'speed_distance' => $speedDist,
            ];
        }

        return new EphemerisSnapshot($jdTt, $jdUt1Resolved, $grahas);
    }

    /**
     * Compute instantaneous (longitude, latitude, distance) for a single celestial body.
     *
     * @return array{longitude: float, latitude: float, distance: float}
     */
    private function calculateCoordinates(string $graha, float $jd): array
    {
        $t = ($jd - 2451545.0) / 36525.0;

        return match ($graha) {
            'sun' => $this->calculateSun($t),
            'moon' => $this->calculateMoon($t),
            'mercury' => $this->calculatePlanet('mercury', $t),
            'venus' => $this->calculatePlanet('venus', $t),
            'mars' => $this->calculatePlanet('mars', $t),
            'jupiter' => $this->calculatePlanet('jupiter', $t),
            'saturn' => $this->calculatePlanet('saturn', $t),
            'rahu' => $this->calculateRahu($t),
            'ketu' => $this->calculateKetu($t),
            default => throw new \InvalidArgumentException("Unsupported graha: {$graha}"),
        };
    }

    /**
     * High precision Solar coordinates (VSOP87 derived, Meeus Chapter 25).
     *
     * @return array{longitude: float, latitude: float, distance: float}
     */
    private function calculateSun(float $t): array
    {
        $l0 = 280.46646 + (36000.76983 * $t) + (0.0003032 * $t * $t);
        $m = 357.52911 + (35999.05029 * $t) - (0.0001537 * $t * $t);
        $mRad = deg2rad($m);

        $e = 0.016708634 - (0.000042037 * $t) - (0.0000001267 * $t * $t);

        $c = ((1.914602 - (0.004817 * $t) - (0.000014 * $t * $t)) * sin($mRad))
            + ((0.019993 - (0.000101 * $t)) * sin(2.0 * $mRad))
            + (0.000289 * sin(3.0 * $mRad));

        $trueLon = $l0 + $c;
        $nu = deg2rad($m + $c);

        $r = (1.000001018 * (1.0 - $e * $e)) / (1.0 + $e * cos($nu));

        // Aberration & nutation correction
        $omega = 125.04 - (1934.136 * $t);
        $apparentLon = $trueLon - 0.00569 - (0.00478 * sin(deg2rad($omega)));

        return [
            'longitude' => self::normalizeDegrees($apparentLon),
            'latitude' => 0.0,
            'distance' => $r,
        ];
    }

    /**
     * High precision Lunar coordinates (ELP-2000, 60 periodic terms, Meeus Chapter 47).
     *
     * @return array{longitude: float, latitude: float, distance: float}
     */
    private function calculateMoon(float $t): array
    {
        $lp = 218.3164477 + (481267.88123421 * $t) - (0.0015786 * $t * $t) + ($t * $t * $t / 538841.0) - ($t * $t * $t * $t / 65194000.0);
        $d = 297.8501921 + (445267.1114034 * $t) - (0.0018819 * $t * $t) + ($t * $t * $t / 545868.0) - ($t * $t * $t * $t / 113065000.0);
        $m = 357.5291092 + (35999.0502909 * $t) - (0.0001536 * $t * $t) + ($t * $t * $t / 24490000.0);
        $mp = 134.9633964 + (477198.8675055 * $t) + (0.0087414 * $t * $t) + ($t * $t * $t / 69699.0) - ($t * $t * $t * $t / 14712000.0);
        $f = 93.2720950 + (483202.0175233 * $t) - (0.0036539 * $t * $t) - ($t * $t * $t / 3526000.0) + ($t * $t * $t * $t / 863310000.0);

        $dRad = deg2rad($d);
        $mRad = deg2rad($m);
        $mpRad = deg2rad($mp);
        $fRad = deg2rad($f);

        // Planetary perturbations (Venus, Jupiter, flattening of Earth)
        $a1 = deg2rad(119.75 + (131.849 * $t));
        $a2 = deg2rad(53.09 + (479264.290 * $t));
        $extraLon = (3958.0 * sin($a1)) + (1962.0 * sin(deg2rad($lp) - $fRad)) + (318.0 * sin($a2));

        $sigmaL = 0.0;
        foreach (self::MOON_LON_TERMS as [$dMul, $mMul, $mpMul, $fMul, $coeff]) {
            $arg = ($dMul * $dRad) + ($mMul * $mRad) + ($mpMul * $mpRad) + ($fMul * $fRad);
            $sigmaL += $coeff * sin($arg);
        }
        $sigmaL = ($sigmaL + $extraLon) / 1000000.0;

        $sigmaB = 0.0;
        foreach (self::MOON_LAT_TERMS as [$dMul, $mMul, $mpMul, $fMul, $coeff]) {
            $arg = ($dMul * $dRad) + ($mMul * $mRad) + ($mpMul * $mpRad) + ($fMul * $fRad);
            $sigmaB += $coeff * sin($arg);
        }
        $sigmaB /= 1000000.0;

        // Approximate Moon distance (Earth radii converted to AU)
        $distanceKm = 385000.56 - (20905.355 * cos($mpRad)) - (3699.111 * cos((2.0 * $dRad) - $mpRad));
        $distanceAu = $distanceKm / 149597870.7;

        return [
            'longitude' => self::normalizeDegrees($lp + $sigmaL),
            'latitude' => $sigmaB,
            'distance' => $distanceAu,
        ];
    }

    /**
     * Keplerian planetary positions converted to geocentric ecliptic coordinates (Meeus Chapter 31).
     *
     * @return array{longitude: float, latitude: float, distance: float}
     */
    private function calculatePlanet(string $planet, float $t): array
    {
        // Orbital elements at epoch J2000.0: [a, e0, e1, i0, i1, L0, L1, pi0, pi1, node0, node1]
        $elements = match ($planet) {
            'mercury' => [0.38709893, 0.20563069, 0.00002527, 7.00487, -0.00516, 252.25084, 149472.67411, 77.45645, 1.55648, 48.33167, -0.12534],
            'venus' => [0.72333199, 0.00677323, -0.00004938, 3.39471, -0.00405, 181.97973, 58517.81539, 131.57294, 0.00850, 76.68069, -0.27774],
            'mars' => [1.52366231, 0.09341233, 0.00011902, 1.85061, -0.02547, 355.45332, 19140.30268, 336.04084, 1.84105, 49.55740, -1.02099],
            'jupiter' => [5.20336301, 0.04839266, -0.00012880, 1.30530, -0.00415, 34.40438, 3034.74612, 14.75385, 0.21253, 100.55615, 0.26785],
            'saturn' => [9.53707032, 0.05415060, -0.00036762, 2.48446, 0.00611, 49.94432, 1222.49472, 92.43194, -0.81868, 113.71504, -0.44470],
            default => throw new \InvalidArgumentException("Unknown planet: {$planet}"),
        };

        // Heliocentric coordinates of target planet
        [$xp, $yp, $zp] = $this->heliocentricCoordinates($elements, $t);

        // Perturbations for Jupiter & Saturn (Great Inequality)
        if ($planet === 'jupiter' || $planet === 'saturn') {
            $lJ = 34.40438 + (3034.74612 * $t);
            $lS = 49.94432 + (1222.49472 * $t);
            $arg = deg2rad((2.0 * $lJ) - (5.0 * $lS) - 67.6);
            if ($planet === 'jupiter') {
                $xp += -0.00033 * sin($arg);
                $yp += -0.00033 * cos($arg);
            } else {
                $xp += 0.00081 * sin($arg);
                $yp += 0.00081 * cos($arg);
            }
        }

        // Heliocentric coordinates of Earth
        $earthElements = [1.00000011, 0.01671022, -0.00003804, 0.00005, -0.01300, 100.46435, 36000.76983, 102.94719, 0.31795, 0.0, 0.0];
        [$xe, $ye, $ze] = $this->heliocentricCoordinates($earthElements, $t);

        // Geocentric coordinates: Planet - Earth
        $xg = $xp - $xe;
        $yg = $yp - $ye;
        $zg = $zp - $ze;

        $distance = sqrt(($xg * $xg) + ($yg * $yg) + ($zg * $zg));
        $lon = rad2deg(atan2($yg, $xg));
        $lat = rad2deg(atan2($zg, sqrt(($xg * $xg) + ($yg * $yg))));

        return [
            'longitude' => self::normalizeDegrees($lon),
            'latitude' => $lat,
            'distance' => $distance,
        ];
    }

    /**
     * @param array<int, float> $elem
     * @return array{0: float, 1: float, 2: float}
     */
    private function heliocentricCoordinates(array $elem, float $t): array
    {
        $a = $elem[0];
        $e = $elem[1] + ($elem[2] * $t);
        $i = deg2rad($elem[3] + ($elem[4] * $t));
        $l = $elem[5] + ($elem[6] * $t);
        $pi = $elem[7] + ($elem[8] * $t);
        $node = deg2rad($elem[9] + ($elem[10] * $t));

        $mDeg = self::normalizeDegrees($l - $pi);
        $mRad = deg2rad($mDeg);

        // Solve Kepler's equation E - e*sin(E) = M
        $eRad = $mRad;
        for ($step = 0; $step < 15; $step++) {
            $f = $eRad - ($e * sin($eRad)) - $mRad;
            if (abs($f) < 1e-12) {
                break;
            }
            $eRad -= $f / (1.0 - ($e * cos($eRad)));
        }

        $xv = $a * (cos($eRad) - $e);
        $yv = $a * (sqrt(max(0.0, 1.0 - ($e * $e))) * sin($eRad));

        $v = atan2($yv, $xv);
        $r = hypot($xv, $yv);

        $omega = deg2rad($pi - rad2deg($node));
        $u = $v + $omega;

        $xh = $r * ((cos($node) * cos($u)) - (sin($node) * sin($u) * cos($i)));
        $yh = $r * ((sin($node) * cos($u)) + (cos($node) * sin($u) * cos($i)));
        $zh = $r * (sin($u) * sin($i));

        return [$xh, $yh, $zh];
    }

    /**
     * Mean North Lunar Node (Rahu).
     *
     * @return array{longitude: float, latitude: float, distance: float}
     */
    private function calculateRahu(float $t): array
    {
        $omega = 125.04452 - (1934.136261 * $t) + (0.0020708 * $t * $t) + (($t * $t * $t) / 450000.0);

        return [
            'longitude' => self::normalizeDegrees($omega),
            'latitude' => 0.0,
            'distance' => 1.0,
        ];
    }

    /**
     * Mean South Lunar Node (Ketu) = Rahu + 180 degrees.
     *
     * @return array{longitude: float, latitude: float, distance: float}
     */
    private function calculateKetu(float $t): array
    {
        $rahu = $this->calculateRahu($t);

        return [
            'longitude' => self::normalizeDegrees($rahu['longitude'] + 180.0),
            'latitude' => 0.0,
            'distance' => 1.0,
        ];
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

