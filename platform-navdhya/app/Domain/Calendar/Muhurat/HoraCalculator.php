<?php

namespace App\Domain\Calendar\Muhurat;

use Carbon\CarbonImmutable;

/**
 * Vedic Planetary Hours (Hora) Calculator.
 * 24 unequal hours (12 day, 12 night) following Chaldean sequence.
 */
final class HoraCalculator
{
    public const CHALDEAN_ORDER = [
        'SUN',
        'VENUS',
        'MERCURY',
        'MOON',
        'SATURN',
        'JUPITER',
        'MARS',
    ];

    public const WEEKDAY_LORDS = [
        0 => 'SUN',     // Sunday
        1 => 'MOON',    // Monday
        2 => 'MARS',    // Tuesday
        3 => 'MERCURY', // Wednesday
        4 => 'JUPITER', // Thursday
        5 => 'VENUS',   // Friday
        6 => 'SATURN',  // Saturday
    ];

    /**
     * Compute 12 daylight planetary hours.
     *
     * @return list<array{
     *     hour: int,
     *     lord: string,
     *     starts_at: CarbonImmutable,
     *     ends_at: CarbonImmutable,
     *     starts_at_us: int,
     *     ends_at_us: int
     * }>
     */
    public static function daylight(CarbonImmutable $sunrise, CarbonImmutable $sunset, int $weekday): array
    {
        $dayLord = self::WEEKDAY_LORDS[$weekday % 7];
        $startIdx = (int) array_search($dayLord, self::CHALDEAN_ORDER, true);

        $durationSeconds = $sunset->getTimestamp() - $sunrise->getTimestamp();
        $hourDuration = $durationSeconds / 12.0;

        $horas = [];
        for ($i = 0; $i < 12; $i++) {
            $lordIdx = ($startIdx + $i) % 7;
            $startSec = (int) round($i * $hourDuration);
            $endSec = (int) round(($i + 1) * $hourDuration);

            $startsAt = $sunrise->addSeconds($startSec);
            $endsAt = $sunrise->addSeconds($endSec);

            $horas[] = [
                'hour' => $i + 1,
                'lord' => self::CHALDEAN_ORDER[$lordIdx],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'starts_at_us' => (int) ($startsAt->getTimestamp() * 1_000_000),
                'ends_at_us' => (int) ($endsAt->getTimestamp() * 1_000_000),
            ];
        }

        return $horas;
    }
}

