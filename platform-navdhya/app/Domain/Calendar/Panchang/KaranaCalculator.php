<?php

namespace App\Domain\Calendar\Panchang;

use App\Domain\Calendar\Astronomy\SolarLongitude;

/**
 * Vedic Karana calculation engine (pure math).
 *
 * A Karana is half of a Tithi (6° elongation of Moon from Sun).
 * There are 60 half-tithis in a lunar month:
 * - 4 fixed (Sthira) Karanas at the extremities (Kimstughna, Shakuni, Chatushpada, Naga)
 * - 7 repeating movable (Chara) Karanas cycling 8 times (56 half-tithis)
 *
 * Closes P-07.
 */
final class KaranaCalculator
{
    public const FIXED_KARANAS = [
        1 => 'KIMSTUGHNA',   // 1st half of Shukla Pratipada
        58 => 'SHAKUNI',      // 2nd half of Krishna Chaturdashi
        59 => 'CHATUSHPADA',  // 1st half of Krishna Amavasya
        60 => 'NAGA',         // 2nd half of Krishna Amavasya
    ];

    public const MOVABLE_KARANAS = [
        0 => 'BAVA',
        1 => 'BALAVA',
        2 => 'KAULAVA',
        3 => 'TAITILA',
        4 => 'GARA',
        5 => 'VANIJA',
        6 => 'VISHTI', // Bhadra
    ];

    /**
     * @param float $elongation Degrees [0, 360)
     * @return array{
     *     number: int,
     *     half_tithi: int,
     *     name: string,
     *     is_vishti_bhadra: bool,
     *     is_fixed: bool,
     *     progress: float
     * }
     */
    public static function calculate(float $elongation): array
    {
        $normalized = SolarLongitude::normalizeDegrees($elongation);
        $halfTithi = (int) floor($normalized / 6.0) + 1;
        if ($halfTithi > 60) {
            $halfTithi = 60;
        }

        $degIntoKarana = fmod($normalized, 6.0);
        $progress = $degIntoKarana / 6.0;

        if (isset(self::FIXED_KARANAS[$halfTithi])) {
            $name = self::FIXED_KARANAS[$halfTithi];
            $isFixed = true;
            $karanaNumber = match ($name) {
                'SHAKUNI' => 8,
                'CHATUSHPADA' => 9,
                'NAGA' => 10,
                'KIMSTUGHNA' => 11,
                default => 1,
            };
        } else {
            $charaIndex = ($halfTithi - 2) % 7;
            $name = self::MOVABLE_KARANAS[$charaIndex];
            $isFixed = false;
            $karanaNumber = $charaIndex + 1; // 1 = BAVA, ..., 7 = VISHTI
        }

        return [
            'number' => $karanaNumber,
            'half_tithi' => $halfTithi,
            'name' => $name,
            'is_vishti_bhadra' => $name === 'VISHTI',
            'is_fixed' => $isFixed,
            'progress' => $progress,
        ];
    }
}

