<?php

namespace App\Domain\Jyotish\Panchang;

use InvalidArgumentException;

/**
 * Dynamic Day/Night Division & Muhurat Octiles Engine (T-JY05-04).
 *
 * Implements:
 * 1. Rahu Kaal, Yamaghanta, Gulika Kaal as true octiles (1/8th) of calculated daylight (resolving D-1).
 * 2. Choghadiya (8 day and 8 night divisions).
 * 3. 24 Planetary Horas (12 day and 12 night divisions).
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class DayDivisionCalculator
{
    /**
     * Day octile index (1..8) for inauspicious windows per Vedic weekday (0=Sunday..6=Saturday).
     */
    public const RAHU_KAAL_OCTILE = [
        0 => 8, // Sunday: 8th octile
        1 => 2, // Monday: 2nd octile
        2 => 7, // Tuesday: 7th octile
        3 => 5, // Wednesday: 5th octile
        4 => 6, // Thursday: 6th octile
        5 => 4, // Friday: 4th octile
        6 => 3, // Saturday: 3rd octile
    ];

    public const YAMAGHANTA_OCTILE = [
        0 => 5, // Sunday
        1 => 4, // Monday
        2 => 3, // Tuesday
        3 => 2, // Wednesday
        4 => 1, // Thursday
        5 => 7, // Friday
        6 => 6, // Saturday
    ];

    public const GULIKA_OCTILE = [
        0 => 7, // Sunday
        1 => 6, // Monday
        2 => 5, // Tuesday
        3 => 4, // Wednesday
        4 => 3, // Thursday
        5 => 2, // Friday
        6 => 1, // Saturday
    ];

    /**
     * Day Choghadiya sequence starting lord for each weekday (0=Sunday..6=Saturday).
     * 7 qualities: Udveg (Sun), Char (Venus), Labh (Mercury), Amrit (Moon), Kaal (Saturn), Shubh (Jupiter), Rog (Mars).
     */
    public const CHOGHADIYA_QUALITIES = [
        'udveg' => ['name' => 'Udveg', 'nature' => 'inauspicious', 'ruling_planet' => 'sun'],
        'char' => ['name' => 'Char', 'nature' => 'good', 'ruling_planet' => 'venus'],
        'labh' => ['name' => 'Labh', 'nature' => 'auspicious', 'ruling_planet' => 'mercury'],
        'amrit' => ['name' => 'Amrit', 'nature' => 'very_auspicious', 'ruling_planet' => 'moon'],
        'kaal' => ['name' => 'Kaal', 'nature' => 'inauspicious', 'ruling_planet' => 'saturn'],
        'shubh' => ['name' => 'Shubh', 'nature' => 'auspicious', 'ruling_planet' => 'jupiter'],
        'rog' => ['name' => 'Rog', 'nature' => 'inauspicious', 'ruling_planet' => 'mars'],
    ];

    public const DAY_CHOGHADIYA_ORDER = [
        0 => ['udveg', 'char', 'labh', 'amrit', 'kaal', 'shubh', 'rog', 'udveg'],       // Sunday
        1 => ['amrit', 'kaal', 'shubh', 'rog', 'udveg', 'char', 'labh', 'amrit'],       // Monday
        2 => ['rog', 'udveg', 'char', 'labh', 'amrit', 'kaal', 'shubh', 'rog'],         // Tuesday
        3 => ['labh', 'amrit', 'kaal', 'shubh', 'rog', 'udveg', 'char', 'labh'],         // Wednesday
        4 => ['shubh', 'rog', 'udveg', 'char', 'labh', 'amrit', 'kaal', 'shubh'],       // Thursday
        5 => ['char', 'labh', 'amrit', 'kaal', 'shubh', 'rog', 'udveg', 'char'],         // Friday
        6 => ['kaal', 'shubh', 'rog', 'udveg', 'char', 'labh', 'amrit', 'kaal'],         // Saturday
    ];

    /**
     * Planetary Hora order (Chaldean descending order of speed): Sun, Venus, Mercury, Moon, Saturn, Jupiter, Mars.
     */
    public const HORA_LORDS = ['sun', 'venus', 'mercury', 'moon', 'saturn', 'jupiter', 'mars'];

    public const WEEKDAY_FIRST_HORA = [
        0 => 'sun',     // Sunday starts with Sun
        1 => 'moon',    // Monday starts with Moon
        2 => 'mars',    // Tuesday starts with Mars
        3 => 'mercury', // Wednesday starts with Mercury
        4 => 'jupiter', // Thursday starts with Jupiter
        5 => 'venus',   // Friday starts with Venus
        6 => 'saturn',  // Saturday starts with Saturn
    ];

    /**
     * Compute octile intervals and inauspicious periods for a day.
     *
     * @param float $sunriseJd
     * @param float $sunsetJd
     * @param int $weekday (0=Sunday..6=Saturday)
     * @return array{
     *     rahu_kaal: array{start_jd: float, end_jd: float, octile: int},
     *     yamaghanta: array{start_jd: float, end_jd: float, octile: int},
     *     gulika_kaal: array{start_jd: float, end_jd: float, octile: int},
     *     choghadiya: list<array{index: int, code: string, name: string, nature: string, start_jd: float, end_jd: float}>
     * }
     */
    public static function calculateDaytimeDivisions(
        float $sunriseJd,
        float $sunsetJd,
        int $weekday
    ): array {
        if ($sunsetJd <= $sunriseJd) {
            throw new InvalidArgumentException("Sunset JD ({$sunsetJd}) must be greater than Sunrise JD ({$sunriseJd}).");
        }

        $w = ($weekday % 7 + 7) % 7;
        $daylightDuration = $sunsetJd - $sunriseJd;
        $octileSpan = $daylightDuration / 8.0;

        // Rahu Kaal
        $rahuOctile = self::RAHU_KAAL_OCTILE[$w];
        $rahuStart = $sunriseJd + (($rahuOctile - 1) * $octileSpan);
        $rahuEnd = $rahuStart + $octileSpan;

        // Yamaghanta
        $yamaOctile = self::YAMAGHANTA_OCTILE[$w];
        $yamaStart = $sunriseJd + (($yamaOctile - 1) * $octileSpan);
        $yamaEnd = $yamaStart + $octileSpan;

        // Gulika
        $gulikaOctile = self::GULIKA_OCTILE[$w];
        $gulikaStart = $sunriseJd + (($gulikaOctile - 1) * $octileSpan);
        $gulikaEnd = $gulikaStart + $octileSpan;

        // Choghadiya
        $choghadiyaCodes = self::DAY_CHOGHADIYA_ORDER[$w];
        $choghadiyas = [];

        for ($i = 0; $i < 8; $i++) {
            $code = $choghadiyaCodes[$i];
            $meta = self::CHOGHADIYA_QUALITIES[$code];
            $start = $sunriseJd + ($i * $octileSpan);
            $end = $start + $octileSpan;

            $choghadiyas[] = [
                'index' => $i + 1,
                'code' => $code,
                'name' => $meta['name'],
                'nature' => $meta['nature'],
                'ruling_planet' => $meta['ruling_planet'],
                'start_jd' => $start,
                'end_jd' => $end,
            ];
        }

        return [
            'rahu_kaal' => [
                'start_jd' => $rahuStart,
                'end_jd' => $rahuEnd,
                'octile' => $rahuOctile,
            ],
            'yamaghanta' => [
                'start_jd' => $yamaStart,
                'end_jd' => $yamaEnd,
                'octile' => $yamaOctile,
            ],
            'gulika_kaal' => [
                'start_jd' => $gulikaStart,
                'end_jd' => $gulikaEnd,
                'octile' => $gulikaOctile,
            ],
            'choghadiya' => $choghadiyas,
        ];
    }
}

