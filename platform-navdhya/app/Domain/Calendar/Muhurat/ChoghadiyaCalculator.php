<?php

namespace App\Domain\Calendar\Muhurat;

use Carbon\CarbonImmutable;

/**
 * Choghadiya Calculator for Vedic electional astrology.
 * Computes 8 daylight and 8 night Choghadiya intervals based on astronomical sunrise and sunset.
 */
final class ChoghadiyaCalculator
{
    public const CHOGHADIYA_ORDER = [
        'UDVEGA', // 0: Sun (Inauspicious)
        'CHARA',  // 1: Venus (Auspicious / Neutral)
        'LABHA',  // 2: Mercury (Auspicious)
        'AMRITA', // 3: Moon (Highly Auspicious)
        'KALA',   // 4: Saturn (Inauspicious)
        'SHUBHA', // 5: Jupiter (Highly Auspicious)
        'ROGA',   // 6: Mars (Inauspicious)
    ];

    public const DAY_STARTS = [
        0 => 0, // Sunday: UDVEGA
        1 => 3, // Monday: AMRITA
        2 => 6, // Tuesday: ROGA
        3 => 2, // Wednesday: LABHA
        4 => 5, // Thursday: SHUBHA
        5 => 1, // Friday: CHARA
        6 => 4, // Saturday: KALA
    ];

    public const NIGHT_STARTS = [
        0 => 5, // Sunday: SHUBHA
        1 => 1, // Monday: CHARA
        2 => 4, // Tuesday: KALA
        3 => 0, // Wednesday: UDVEGA
        4 => 3, // Thursday: AMRITA
        5 => 6, // Friday: ROGA
        6 => 2, // Saturday: LABHA
    ];

    /**
     * Auspiciousness map:
     * AMRITA, SHUBHA, LABHA = Auspicious (Shubh)
     * CHARA = Neutral / Good for journey (Chara)
     * ROGA, KALA, UDVEGA = Inauspicious (Ashubh)
     */
    public const AUSPICIOUS_TYPES = [
        'AMRITA' => 'HIGHLY_AUSPICIOUS',
        'SHUBHA' => 'HIGHLY_AUSPICIOUS',
        'LABHA' => 'AUSPICIOUS',
        'CHARA' => 'NEUTRAL',
        'ROGA' => 'INAUSPICIOUS',
        'KALA' => 'INAUSPICIOUS',
        'UDVEGA' => 'INAUSPICIOUS',
    ];

    /**
     * Compute the 8 daylight Choghadiyas.
     *
     * @return list<array{
     *     slot: int,
     *     name: string,
     *     nature: string,
     *     starts_at: CarbonImmutable,
     *     ends_at: CarbonImmutable,
     *     starts_at_us: int,
     *     ends_at_us: int
     * }>
     */
    public static function daylight(CarbonImmutable $sunrise, CarbonImmutable $sunset, int $weekday): array
    {
        $octants = DayDivision::daylightOctants($sunrise, $sunset);
        $startIdx = self::DAY_STARTS[$weekday % 7];

        $results = [];
        for ($i = 0; $i < 8; $i++) {
            $choghadiyaIdx = ($startIdx + $i) % 7;
            $name = self::CHOGHADIYA_ORDER[$choghadiyaIdx];

            $results[] = [
                'slot' => $i + 1,
                'name' => $name,
                'nature' => self::AUSPICIOUS_TYPES[$name],
                'starts_at' => $octants[$i]['starts_at'],
                'ends_at' => $octants[$i]['ends_at'],
                'starts_at_us' => $octants[$i]['starts_at_us'],
                'ends_at_us' => $octants[$i]['ends_at_us'],
            ];
        }

        return $results;
    }
}

