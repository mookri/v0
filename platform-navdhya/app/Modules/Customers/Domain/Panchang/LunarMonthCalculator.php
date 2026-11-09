<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Lunar month determination engine with dual tradition naming (pure math).
 *
 * Traditions:
 * - Amanta (Mukhya): Month ends on New Moon (Amavasya).
 * - Purnimanta (Gauna): Month ends on Full Moon (Purnima).
 *
 * In Shukla Paksha (waxing), Amanta and Purnimanta month names are IDENTICAL.
 * In Krishna Paksha (waning), Purnimanta leads Amanta by exactly one month name.
 */
final class LunarMonthCalculator
{
    public const MONTHS = [
        1 => 'CHAITRA',
        2 => 'VAISHAKHA',
        3 => 'JYESHTHA',
        4 => 'ASHADHA',
        5 => 'SHRAVANA',
        6 => 'BHADRAPADA',
        7 => 'ASHVINA',
        8 => 'KARTIKA',
        9 => 'MARGASHIRSHA',
        10 => 'PAUSHA',
        11 => 'MAGHA',
        12 => 'PHALGUNA',
    ];

    /**
     * @param float $sunLongitude Apparent sun longitude in degrees [0, 360)
     * @param int $tithiNumber Tithi number [1, 30]
     * @param bool $isAdhika Whether the current lunar month is intercalary (Adhika)
     * @return array{amanta: string, purnimanta: string, is_adhika: bool, solar_rashi_index: int}
     */
    public static function calculate(float $sunLongitude, int $tithiNumber, bool $isAdhika = false): array
    {
        // Solar rashi index: 1 = Mesha (0°-30°), 2 = Vrishabha, ..., 12 = Meena
        $solarRashiIndex = (int) floor(SolarLongitude::normalizeDegrees($sunLongitude) / 30.0) + 1;
        if ($solarRashiIndex > 12) {
            $solarRashiIndex = 12;
        }

        // Base Amanta lunar month corresponds to the solar Rashi
        $amantaMonthIndex = $solarRashiIndex;
        $amantaMonthName = self::MONTHS[$amantaMonthIndex];

        // In Purnimanta tradition:
        // - Shukla paksha (tithi 1..15): identical to Amanta
        // - Krishna paksha (tithi 16..30): leads by one month
        $isShukla = $tithiNumber <= 15;
        if ($isShukla) {
            $purnimantaMonthIndex = $amantaMonthIndex;
        } else {
            $purnimantaMonthIndex = ($amantaMonthIndex % 12) + 1;
        }
        $purnimantaMonthName = self::MONTHS[$purnimantaMonthIndex];

        if ($isAdhika) {
            $amantaMonthName = "ADHIKA_{$amantaMonthName}";
            $purnimantaMonthName = "ADHIKA_{$purnimantaMonthName}";
        }

        return [
            'amanta' => $amantaMonthName,
            'purnimanta' => $purnimantaMonthName,
            'is_adhika' => $isAdhika,
            'solar_rashi_index' => $solarRashiIndex,
        ];
    }
}
