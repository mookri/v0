<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Tithi and Paksha astronomical division engine (pure math).
 * Each tithi spans exactly 12 degrees of angular elongation between Moon and Sun.
 */
final class TithiCalculator
{
    public const PAKSHA_SHUKLA = 'SHUKLA';
    public const PAKSHA_KRISHNA = 'KRISHNA';

    public const TITHI_NAMES = [
        1 => 'PRATIPADA',
        2 => 'DWITIYA',
        3 => 'TRITIYA',
        4 => 'CHATURTHI',
        5 => 'PANCHAMI',
        6 => 'SHASHTI',
        7 => 'SAPTAMI',
        8 => 'ASHTAMI',
        9 => 'NAVAMI',
        10 => 'DASHAMI',
        11 => 'EKADASHI',
        12 => 'DWADASHI',
        13 => 'TRAYODASHI',
        14 => 'CHATURDASHI',
        15 => 'PURNIMA',
        16 => 'PRATIPADA',
        17 => 'DWITIYA',
        18 => 'TRITIYA',
        19 => 'CHATURTHI',
        20 => 'PANCHAMI',
        21 => 'SHASHTI',
        22 => 'SAPTAMI',
        23 => 'ASHTAMI',
        24 => 'NAVAMI',
        25 => 'DASHAMI',
        26 => 'EKADASHI',
        27 => 'DWADASHI',
        28 => 'TRAYODASHI',
        29 => 'CHATURDASHI',
        30 => 'AMAVASYA',
    ];

    /**
     * @param float $elongation Degrees [0, 360)
     * @return array{number: int, name: string, paksha: string, index_in_paksha: int, progress: float}
     */
    public static function calculate(float $elongation): array
    {
        $clamped = SolarLongitude::normalizeDegrees($elongation);
        $tithiIndex = (int) floor($clamped / 12.0) + 1;
        if ($tithiIndex > 30) {
            $tithiIndex = 30;
        }

        $isShukla = $tithiIndex <= 15;
        $paksha = $isShukla ? self::PAKSHA_SHUKLA : self::PAKSHA_KRISHNA;
        $indexInPaksha = $isShukla ? $tithiIndex : ($tithiIndex - 15);

        // Progress fraction through the 12-degree tithi span
        $degreesIntoTithi = fmod($clamped, 12.0);
        $progress = $degreesIntoTithi / 12.0;

        return [
            'number' => $tithiIndex,
            'name' => self::TITHI_NAMES[$tithiIndex],
            'paksha' => $paksha,
            'index_in_paksha' => $indexInPaksha,
            'progress' => $progress,
        ];
    }
}
