<?php

namespace App\Domain\Calendar\Festivals;

use Carbon\CarbonImmutable;

/**
 * Classical Observance Interval Anchor Resolver.
 * Computes specific Vedic temporal windows (Pradosha, Nishita, Madhyahna, Moonrise).
 */
final class ObservanceAnchorResolver
{
    /**
     * Pradosha Kaal: Sunset to Sunset + 2h 24m (first 3 muhurtas of night).
     *
     * @return array{name: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, starts_at_us: int, ends_at_us: int}
     */
    public static function pradosha(CarbonImmutable $sunset): array
    {
        $startsAt = $sunset;
        $endsAt = $sunset->addMinutes(144); // 2 hours 24 minutes

        return [
            'name' => 'PRADOSHA',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'starts_at_us' => (int) ($startsAt->getTimestamp() * 1_000_000),
            'ends_at_us' => (int) ($endsAt->getTimestamp() * 1_000_000),
        ];
    }

    /**
     * Nishita Kaal: Centered on local midnight (approx 23:40 - 00:28), spanning 1 muhurta (48 mins nominal).
     *
     * @return array{name: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, starts_at_us: int, ends_at_us: int}
     */
    public static function nishita(CarbonImmutable $sunset, CarbonImmutable $nextSunrise): array
    {
        $nightDurationSeconds = $nextSunrise->getTimestamp() - $sunset->getTimestamp();
        $solarMidnight = $sunset->addSeconds((int) ($nightDurationSeconds / 2));

        $muhurtaDuration = $nightDurationSeconds / 15.0;
        $halfMuhurta = (int) round($muhurtaDuration / 2.0);

        $startsAt = $solarMidnight->subSeconds($halfMuhurta);
        $endsAt = $solarMidnight->addSeconds($halfMuhurta);

        return [
            'name' => 'NISHITA',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'starts_at_us' => (int) ($startsAt->getTimestamp() * 1_000_000),
            'ends_at_us' => (int) ($endsAt->getTimestamp() * 1_000_000),
        ];
    }

    /**
     * Madhyahna Kaal: Daylight divided into 5 equal parts, 3rd part (midday).
     *
     * @return array{name: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, starts_at_us: int, ends_at_us: int}
     */
    public static function madhyahna(CarbonImmutable $sunrise, CarbonImmutable $sunset): array
    {
        $dayDurationSeconds = $sunset->getTimestamp() - $sunrise->getTimestamp();
        $partDuration = $dayDurationSeconds / 5.0;

        $startsAt = $sunrise->addSeconds((int) round(2 * $partDuration));
        $endsAt = $sunrise->addSeconds((int) round(3 * $partDuration));

        return [
            'name' => 'MADHYAHNA',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'starts_at_us' => (int) ($startsAt->getTimestamp() * 1_000_000),
            'ends_at_us' => (int) ($endsAt->getTimestamp() * 1_000_000),
        ];
    }
}

