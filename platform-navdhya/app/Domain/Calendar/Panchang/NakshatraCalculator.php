<?php

namespace App\Domain\Calendar\Panchang;

use App\Domain\Calendar\Astronomy\SolarLongitude;

/**
 * Vedic Nakshatra division engine (pure math).
 * The 360-degree zodiac is divided into 27 equal lunar mansions of 13°20' (13.333333°).
 * Each Nakshatra consists of 4 Padas of 3°20' (3.333333°) each.
 */
final class NakshatraCalculator
{
    public const NAKSHATRAS = [
        1 => 'ASHWINI',
        2 => 'BHARANI',
        3 => 'KRITTIKA',
        4 => 'ROHINI',
        5 => 'MRIGASHIRA',
        6 => 'ARDRA',
        7 => 'PUNARVASU',
        8 => 'PUSHYA',
        9 => 'ASHLESHA',
        10 => 'MAGHA',
        11 => 'PURVA_PHALGUNI',
        12 => 'UTTARA_PHALGUNI',
        13 => 'HASTA',
        14 => 'CHITRA',
        15 => 'SWATI',
        16 => 'VISHAKHA',
        17 => 'ANURADHA',
        18 => 'JYESHTHA',
        19 => 'MULA',
        20 => 'PURVA_ASHADHA',
        21 => 'UTTARA_ASHADHA',
        22 => 'SHRAVANA',
        23 => 'DHANISHTA',
        24 => 'SHATABHISHA',
        25 => 'PURVA_BHADRAPADA',
        26 => 'UTTARA_BHADRAPADA',
        27 => 'REVATI',
    ];

    /**
     * @param float $moonLongitude Degrees [0, 360)
     * @return array{number: int, name: string, pada: int, progress: float}
     */
    public static function calculate(float $moonLongitude): array
    {
        $clamped = SolarLongitude::normalizeDegrees($moonLongitude);
        $span = 360.0 / 27.0; // 13.333333333333334 degrees

        $index = (int) floor($clamped / $span) + 1;
        if ($index > 27) {
            $index = 27;
        }

        $degIntoNakshatra = fmod($clamped, $span);
        $padaSpan = $span / 4.0; // 3.3333333333333335 degrees
        $pada = (int) floor($degIntoNakshatra / $padaSpan) + 1;
        if ($pada > 4) {
            $pada = 4;
        }

        $progress = $degIntoNakshatra / $span;

        return [
            'number' => $index,
            'name' => self::NAKSHATRAS[$index],
            'pada' => $pada,
            'progress' => $progress,
        ];
    }
}

