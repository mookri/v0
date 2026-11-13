<?php

namespace App\Domain\Calendar\Panchang;

use App\Domain\Calendar\Astronomy\SolarLongitude;

/**
 * Lunar Phase, illumination fraction, and lunar age calculator.
 */
final class MoonPhaseCalculator
{
    public const SYNODIC_MONTH_DAYS = 29.530588853;

    /**
     * @param float $elongation Degrees [0, 360)
     * @return array{
     *     phase_name: string,
     *     illumination_fraction: float,
     *     age_days: float,
     *     is_waxing: bool
     * }
     */
    public static function calculate(float $elongation): array
    {
        $elong = SolarLongitude::normalizeDegrees($elongation);

        // Illumination fraction: k = (1 - cos(elongation)) / 2
        $illumination = (1.0 - cos(deg2rad($elong))) / 2.0;

        // Age in days since new moon
        $ageDays = ($elong / 360.0) * self::SYNODIC_MONTH_DAYS;

        $isWaxing = $elong < 180.0;

        $phaseName = match (true) {
            $elong < 10.0 || $elong >= 350.0 => 'NEW_MOON',
            $elong < 80.0 => 'WAXING_CRESCENT',
            $elong < 100.0 => 'FIRST_QUARTER',
            $elong < 170.0 => 'WAXING_GIBBOUS',
            $elong < 190.0 => 'FULL_MOON',
            $elong < 260.0 => 'WANING_GIBBOUS',
            $elong < 280.0 => 'THIRD_QUARTER',
            default => 'WANING_CRESCENT',
        };

        return [
            'phase_name' => $phaseName,
            'illumination_fraction' => round($illumination, 4),
            'age_days' => round($ageDays, 2),
            'is_waxing' => $isWaxing,
        ];
    }
}

