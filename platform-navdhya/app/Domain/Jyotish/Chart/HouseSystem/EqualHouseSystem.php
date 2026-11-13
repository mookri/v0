<?php

namespace App\Domain\Jyotish\Chart\HouseSystem;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;

/**
 * Equal House System (T-JY03-02).
 * Each house spans exactly 30°, starting with the exact Ascendant degree.
 */
final readonly class EqualHouseSystem implements HouseSystemInterface, ZodiacConstants
{
    public function systemCode(): string
    {
        return 'EQUAL_HOUSE';
    }

    public function systemName(): string
    {
        return 'Equal House System';
    }

    public function calculateCusps(
        float $ascendantDegrees,
        float $mcDegrees,
        float $latitude = 0.0,
        float $ramcDegrees = 0.0,
        float $obliquityDegrees = 23.44
    ): array {
        $asc = Angle::normalize($ascendantDegrees);
        $cusps = [];

        for ($h = 1; $h <= 12; $h++) {
            $cuspDeg = Angle::normalize($asc + (($h - 1) * 30.0));
            $rashiIndex = (int) floor($cuspDeg / self::RASHI_SPAN_DEGREES);
            if ($rashiIndex >= 12) {
                $rashiIndex = 11;
            }
            $degreesInRashi = $cuspDeg - ($rashiIndex * self::RASHI_SPAN_DEGREES);
            $rashiMeta = self::RASHIS[$rashiIndex];

            $sandhiStart = Angle::normalize($cuspDeg - 15.0);
            $sandhiEnd = Angle::normalize($cuspDeg + 15.0);

            $cusps[$h] = [
                'house' => $h,
                'cusp_degrees' => $cuspDeg,
                'dms' => (new Angle($cuspDeg))->toDms(),
                'rashi_index' => $rashiIndex,
                'rashi_name' => $rashiMeta['name'],
                'degrees_in_rashi' => $degreesInRashi,
                'sandhi_start' => $sandhiStart,
                'sandhi_end' => $sandhiEnd,
                'span_degrees' => 30.0,
            ];
        }

        return $cusps;
    }
}

