<?php

namespace App\Domain\Calendar\Panchang;

/**
 * Vedic Tithi (lunar phase) calculation engine (pure math).
 * The 360-degree synodic lunar cycle is divided into 30 equal tithis of 12 degrees each:
 * - 1 to 15: Shukla Paksha (waxing, culminating in Purnima)
 * - 16 to 30: Krishna Paksha (waning, culminating in Amavasya)
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
        6 => 'SHASHTHI',
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
        21 => 'SHASHTHI',
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
        $clamped = fmod($elongation, 360.0);
        if ($clamped < 0.0) {
            $clamped += 360.0;
        }

        $index = (int) floor($clamped / 12.0) + 1;
        if ($index > 30) {
            $index = 30;
        }

        $degIntoTithi = fmod($clamped, 12.0);
        $progress = $degIntoTithi / 12.0;

        $paksha = $index <= 15 ? self::PAKSHA_SHUKLA : self::PAKSHA_KRISHNA;
        $indexInPaksha = $index <= 15 ? $index : $index - 15;

        return [
            'number' => $index,
            'name' => self::TITHI_NAMES[$index],
            'paksha' => $paksha,
            'index_in_paksha' => $indexInPaksha,
            'progress' => $progress,
        ];
    }
}

