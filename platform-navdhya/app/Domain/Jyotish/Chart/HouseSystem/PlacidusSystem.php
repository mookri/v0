<?php

namespace App\Domain\Jyotish\Chart\HouseSystem;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;

/**
 * Placidus House System (T-JY03-02).
 * Semi-arc division system widely utilized in Krishnamurti Paddhati (KP).
 *
 * Falls back safely to Sripati / Porphyry in polar regions (|lat| >= 66°).
 */
final readonly class PlacidusSystem implements HouseSystemInterface, ZodiacConstants
{
    private SripatiSystem $sripatiFallback;

    public function __construct()
    {
        $this->sripatiFallback = new SripatiSystem();
    }

    public function systemCode(): string
    {
        return 'PLACIDUS';
    }

    public function systemName(): string
    {
        return 'Placidus (KP Semi-Arc System)';
    }

    public function calculateCusps(
        float $ascendantDegrees,
        float $mcDegrees,
        float $latitude = 0.0,
        float $ramcDegrees = 0.0,
        float $obliquityDegrees = 23.44
    ): array {
        // Polar latitudes fail Placidus semi-arc convergence; fall back to Sripati
        if (abs($latitude) >= 66.0) {
            return $this->sripatiFallback->calculateCusps(
                $ascendantDegrees,
                $mcDegrees,
                $latitude,
                $ramcDegrees,
                $obliquityDegrees
            );
        }

        $asc = Angle::normalize($ascendantDegrees);
        $mc = Angle::normalize($mcDegrees);
        $desc = Angle::normalize($asc + 180.0);
        $ic = Angle::normalize($mc + 180.0);

        $epsRad = deg2rad($obliquityDegrees);
        $phiRad = deg2rad($latitude);

        // Houses 11, 12, 2, 3 via iterative semi-arc solver
        $h11 = $this->solveIntermediateCusp($ramcDegrees + 30.0, 1.0 / 3.0, $phiRad, $epsRad, true);
        $h12 = $this->solveIntermediateCusp($ramcDegrees + 60.0, 2.0 / 3.0, $phiRad, $epsRad, true);
        $h2  = $this->solveIntermediateCusp($ramcDegrees + 120.0, 2.0 / 3.0, $phiRad, $epsRad, false);
        $h3  = $this->solveIntermediateCusp($ramcDegrees + 150.0, 1.0 / 3.0, $phiRad, $epsRad, false);

        // Opposite cusps (7 to 10 are diametric opposites of 1 to 4)
        $h5 = Angle::normalize($h11 + 180.0);
        $h6 = Angle::normalize($h12 + 180.0);
        $h8 = Angle::normalize($h2 + 180.0);
        $h9 = Angle::normalize($h3 + 180.0);

        $cuspLongitudes = [
            1 => $asc,
            2 => $h2,
            3 => $h3,
            4 => $ic,
            5 => $h5,
            6 => $h6,
            7 => $desc,
            8 => $h8,
            9 => $h9,
            10 => $mc,
            11 => $h11,
            12 => $h12,
        ];

        // Boundaries (sandhis) as midpoints between consecutive cusps
        $sandhis = [];
        for ($h = 1; $h <= 12; $h++) {
            $next = ($h % 12) + 1;
            $arcBetween = Angle::normalize($cuspLongitudes[$next] - $cuspLongitudes[$h]);
            $sandhis[$h] = Angle::normalize($cuspLongitudes[$h] + ($arcBetween / 2.0));
        }

        $cusps = [];
        for ($h = 1; $h <= 12; $h++) {
            $prev = ($h === 1) ? 12 : $h - 1;
            $sandhiStart = $sandhis[$prev];
            $sandhiEnd = $sandhis[$h];
            $span = Angle::normalize($sandhiEnd - $sandhiStart);

            $cuspDeg = $cuspLongitudes[$h];
            $rashiIndex = (int) floor($cuspDeg / self::RASHI_SPAN_DEGREES);
            if ($rashiIndex >= 12) {
                $rashiIndex = 11;
            }
            $degreesInRashi = $cuspDeg - ($rashiIndex * self::RASHI_SPAN_DEGREES);
            $rashiMeta = self::RASHIS[$rashiIndex];

            $cusps[$h] = [
                'house' => $h,
                'cusp_degrees' => $cuspDeg,
                'dms' => (new Angle($cuspDeg))->toDms(),
                'rashi_index' => $rashiIndex,
                'rashi_name' => $rashiMeta['name'],
                'degrees_in_rashi' => $degreesInRashi,
                'sandhi_start' => $sandhiStart,
                'sandhi_end' => $sandhiEnd,
                'span_degrees' => $span,
            ];
        }

        return $cusps;
    }

    /**
     * Iterative Placidus semi-arc solver for intermediate cusps.
     */
    private function solveIntermediateCusp(
        float $targetRaDeg,
        float $semiArcFactor,
        float $phiRad,
        float $epsRad,
        bool $diurnal
    ): float {
        $raRad = deg2rad($targetRaDeg);

        // 6 iterations of Newton-Raphson / successive approximation
        for ($iter = 0; $iter < 6; $iter++) {
            // declination delta
            $sinDelta = sin($epsRad) * sin($raRad);
            $cosDelta = sqrt(max(0.0, 1.0 - ($sinDelta * $sinDelta)));
            if ($cosDelta < 1e-6) {
                break;
            }
            $tanDelta = $sinDelta / $cosDelta;

            // Semi-arc argument
            $val = tan($phiRad) * $tanDelta;
            if (abs($val) > 0.9999) {
                $val = ($val < 0) ? -0.9999 : 0.9999;
            }

            $ad = asin($val); // ascensional difference
            $correction = $semiArcFactor * $ad;

            $raRad = deg2rad($targetRaDeg) + ($diurnal ? -$correction : $correction);
        }

        // Convert RA to ecliptic longitude: tan(lambda) = sin(alpha) / (cos(alpha) * cos(eps))
        $y = sin($raRad);
        $x = cos($raRad) * cos($epsRad);

        return Angle::normalize(rad2deg(atan2($y, $x)));
    }
}

