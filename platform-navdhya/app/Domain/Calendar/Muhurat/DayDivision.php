<?php

namespace App\Domain\Calendar\Muhurat;

use Carbon\CarbonImmutable;

/**
 * Classical Day Division Engine (Ahnika & Ashta-yama).
 * Strictly zero hardcoded clock ranges: dynamically derived from astronomical sunrise and sunset.
 */
final class DayDivision
{
    /**
     * Rahu Kaal 8-part slot indices (1-indexed) by weekday (0 = Sun, 1 = Mon, ..., 6 = Sat).
     */
    public const RAHU_KAAL_PARTS = [
        0 => 8, // Sunday: 8th part
        1 => 2, // Monday: 2nd part
        2 => 7, // Tuesday: 7th part
        3 => 5, // Wednesday: 5th part
        4 => 6, // Thursday: 6th part
        5 => 4, // Friday: 4th part
        6 => 3, // Saturday: 3rd part
    ];

    /**
     * Yamaganda 8-part slot indices by weekday.
     */
    public const YAMAGANDA_PARTS = [
        0 => 5, // Sunday
        1 => 4, // Monday
        2 => 3, // Tuesday
        3 => 2, // Wednesday
        4 => 1, // Thursday
        5 => 7, // Friday
        6 => 6, // Saturday
    ];

    /**
     * Gulika 8-part slot indices by weekday.
     */
    public const GULIKA_PARTS = [
        0 => 7, // Sunday
        1 => 6, // Monday
        2 => 5, // Tuesday
        3 => 4, // Wednesday
        4 => 3, // Thursday
        5 => 2, // Friday
        6 => 1, // Saturday
    ];

    /**
     * Calculate 8 equal divisions of daylight.
     *
     * @return list<array{part: int, starts_at: CarbonImmutable, ends_at: CarbonImmutable, starts_at_us: int, ends_at_us: int}>
     */
    public static function daylightOctants(CarbonImmutable $sunrise, CarbonImmutable $sunset): array
    {
        $durationSeconds = $sunset->getTimestamp() - $sunrise->getTimestamp();
        $partDuration = $durationSeconds / 8.0;

        $octants = [];
        for ($i = 0; $i < 8; $i++) {
            $startSec = (int) round($i * $partDuration);
            $endSec = (int) round(($i + 1) * $partDuration);

            $startsAt = $sunrise->addSeconds($startSec);
            $endsAt = $sunrise->addSeconds($endSec);

            $octants[] = [
                'part' => $i + 1,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'starts_at_us' => (int) ($startsAt->getTimestamp() * 1_000_000),
                'ends_at_us' => (int) ($endsAt->getTimestamp() * 1_000_000),
            ];
        }

        return $octants;
    }

    /**
     * Calculate 15 equal daylight Muhurtas (nominally ~48 min).
     *
     * @return list<array{muhurta: int, starts_at: CarbonImmutable, ends_at: CarbonImmutable, starts_at_us: int, ends_at_us: int}>
     */
    public static function daylightMuhurtas(CarbonImmutable $sunrise, CarbonImmutable $sunset): array
    {
        $durationSeconds = $sunset->getTimestamp() - $sunrise->getTimestamp();
        $partDuration = $durationSeconds / 15.0;

        $muhurtas = [];
        for ($i = 0; $i < 15; $i++) {
            $startSec = (int) round($i * $partDuration);
            $endSec = (int) round(($i + 1) * $partDuration);

            $startsAt = $sunrise->addSeconds($startSec);
            $endsAt = $sunrise->addSeconds($endSec);

            $muhurtas[] = [
                'muhurta' => $i + 1,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'starts_at_us' => (int) ($startsAt->getTimestamp() * 1_000_000),
                'ends_at_us' => (int) ($endsAt->getTimestamp() * 1_000_000),
            ];
        }

        return $muhurtas;
    }

    /**
     * Calculate Rahu Kaal window for a given date and daylight interval.
     *
     * @return array{name: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, starts_at_us: int, ends_at_us: int}
     */
    public static function rahuKaal(CarbonImmutable $sunrise, CarbonImmutable $sunset, int $weekday): array
    {
        $octants = self::daylightOctants($sunrise, $sunset);
        $targetPart = self::RAHU_KAAL_PARTS[$weekday % 7];
        $slot = $octants[$targetPart - 1];

        return [
            'name' => 'RAHU_KAAL',
            'starts_at' => $slot['starts_at'],
            'ends_at' => $slot['ends_at'],
            'starts_at_us' => $slot['starts_at_us'],
            'ends_at_us' => $slot['ends_at_us'],
        ];
    }

    /**
     * Calculate Yamaganda window for a given date and daylight interval.
     *
     * @return array{name: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, starts_at_us: int, ends_at_us: int}
     */
    public static function yamaganda(CarbonImmutable $sunrise, CarbonImmutable $sunset, int $weekday): array
    {
        $octants = self::daylightOctants($sunrise, $sunset);
        $targetPart = self::YAMAGANDA_PARTS[$weekday % 7];
        $slot = $octants[$targetPart - 1];

        return [
            'name' => 'YAMAGANDA',
            'starts_at' => $slot['starts_at'],
            'ends_at' => $slot['ends_at'],
            'starts_at_us' => $slot['starts_at_us'],
            'ends_at_us' => $slot['ends_at_us'],
        ];
    }

    /**
     * Calculate Gulika Kaal window for a given date and daylight interval.
     *
     * @return array{name: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, starts_at_us: int, ends_at_us: int}
     */
    public static function gulika(CarbonImmutable $sunrise, CarbonImmutable $sunset, int $weekday): array
    {
        $octants = self::daylightOctants($sunrise, $sunset);
        $targetPart = self::GULIKA_PARTS[$weekday % 7];
        $slot = $octants[$targetPart - 1];

        return [
            'name' => 'GULIKA',
            'starts_at' => $slot['starts_at'],
            'ends_at' => $slot['ends_at'],
            'starts_at_us' => $slot['starts_at_us'],
            'ends_at_us' => $slot['ends_at_us'],
        ];
    }
}

