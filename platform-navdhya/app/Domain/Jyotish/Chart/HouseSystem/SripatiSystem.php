<?php

namespace App\Domain\Jyotish\Chart\HouseSystem;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;

/**
 * Sripati House System (T-JY03-02).
 * Classical Indian quadrant system (Porphyry-variant) based on Sripati Paddhati.
 *
 * House 1 Madhya = Lagna (Ascendant)
 * House 10 Madhya = Midheaven (MC)
 * Quadrants are trisected; Sandhis are exact midpoints between consecutive Madhyas.
 */
final readonly class SripatiSystem implements HouseSystemInterface, ZodiacConstants
{
    public function systemCode(): string
    {
        return 'SRIPATI';
    }

    public function systemName(): string
    {
        return 'Sripati Paddhati (Classical Vedic Quadrant)';
    }

    public function calculateCusps(
        float $ascendantDegrees,
        float $mcDegrees,
        float $latitude = 0.0,
        float $ramcDegrees = 0.0,
        float $obliquityDegrees = 23.44
    ): array {
        $asc = Angle::normalize($ascendantDegrees);
        $mc = Angle::normalize($mcDegrees);
        $desc = Angle::normalize($asc + 180.0);
        $ic = Angle::normalize($mc + 180.0);

        // Quadrant IV: MC (H10) to Ascendant (H1)
        $arcMcToAsc = Angle::normalize($asc - $mc);
        $stepIv = $arcMcToAsc / 3.0;
        $h11 = Angle::normalize($mc + $stepIv);
        $h12 = Angle::normalize($mc + (2.0 * $stepIv));

        // Quadrant I: Ascendant (H1) to IC (H4)
        $arcAscToIc = Angle::normalize($ic - $asc);
        $stepI = $arcAscToIc / 3.0;
        $h2 = Angle::normalize($asc + $stepI);
        $h3 = Angle::normalize($asc + (2.0 * $stepI));

        // Opposite houses (Quadrants II & III)
        $h5 = Angle::normalize($h11 + 180.0);
        $h6 = Angle::normalize($h12 + 180.0);
        $h8 = Angle::normalize($h2 + 180.0);
        $h9 = Angle::normalize($h3 + 180.0);

        // 1-indexed map of Madhyas (cusps)
        $madhyas = [
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

        // Compute Sandhis (boundaries between consecutive house madhyas)
        // Sandhi $h$ is boundary between House $h$ and House ($h % 12) + 1
        $sandhis = [];
        for ($h = 1; $h <= 12; $h++) {
            $next = ($h % 12) + 1;
            $arcBetween = Angle::normalize($madhyas[$next] - $madhyas[$h]);
            $sandhis[$h] = Angle::normalize($madhyas[$h] + ($arcBetween / 2.0));
        }

        $cusps = [];
        for ($h = 1; $h <= 12; $h++) {
            $prev = ($h === 1) ? 12 : $h - 1;
            $sandhiStart = $sandhis[$prev];
            $sandhiEnd = $sandhis[$h];
            $span = Angle::normalize($sandhiEnd - $sandhiStart);

            $cuspDeg = $madhyas[$h];
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
}

