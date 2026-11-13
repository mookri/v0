<?php

namespace App\Domain\Jyotish\Chart\HouseSystem;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;

/**
 * Whole Sign House System (T-JY03-02).
 * Classical Parashari baseline: 1 Rashi = 1 Bhava.
 * House 1 starts at 0° of the Rashi containing the Lagna.
 */
final readonly class WholeSignSystem implements HouseSystemInterface, ZodiacConstants
{
    public function systemCode(): string
    {
        return 'WHOLE_SIGN';
    }

    public function systemName(): string
    {
        return 'Whole Sign (Parashari Baseline)';
    }

    public function calculateCusps(
        float $ascendantDegrees,
        float $mcDegrees,
        float $latitude = 0.0,
        float $ramcDegrees = 0.0,
        float $obliquityDegrees = 23.44
    ): array {
        $ascNormalized = Angle::normalize($ascendantDegrees);
        $lagnaRashiIndex = (int) floor($ascNormalized / self::RASHI_SPAN_DEGREES);
        $offsetInRashi = $ascNormalized - ($lagnaRashiIndex * self::RASHI_SPAN_DEGREES);

        $cusps = [];

        for ($h = 1; $h <= 12; $h++) {
            $rashiIndex = ($lagnaRashiIndex + ($h - 1)) % 12;
            $rashiMeta = self::RASHIS[$rashiIndex];

            // Cusp/Madhya preserves the Lagna degree offset inside each respective sign
            $cuspDeg = Angle::normalize(($rashiIndex * self::RASHI_SPAN_DEGREES) + $offsetInRashi);
            $sandhiStart = Angle::normalize($rashiIndex * self::RASHI_SPAN_DEGREES);
            $sandhiEnd = Angle::normalize(($rashiIndex + 1) * self::RASHI_SPAN_DEGREES);

            $cusps[$h] = [
                'house' => $h,
                'cusp_degrees' => $cuspDeg,
                'dms' => (new Angle($cuspDeg))->toDms(),
                'rashi_index' => $rashiIndex,
                'rashi_name' => $rashiMeta['name'],
                'degrees_in_rashi' => $offsetInRashi,
                'sandhi_start' => $sandhiStart,
                'sandhi_end' => $sandhiEnd,
                'span_degrees' => 30.0,
            ];
        }

        return $cusps;
    }
}

