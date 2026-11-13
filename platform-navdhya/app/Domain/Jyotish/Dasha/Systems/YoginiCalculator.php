<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Dasha\Systems;

use App\Domain\Jyotish\Dasha\Contracts\DashaSystemInterface;
use App\Domain\Jyotish\Dasha\Models\DashaInterval;

final readonly class YoginiCalculator implements DashaSystemInterface
{
    public const SYSTEM_CODE = 'YOGINI';
    public const TOTAL_CYCLE_YEARS = 36.0;
    public const SOLAR_YEAR_SECONDS = 365.25 * 86400.0;

    // 8 Yoginis: Mangala, Pingala, Dhanya, Bhramari, Bhadrika, Ulka, Siddha, Sankata
    public const YOGINI_LORDS = [
        'mangala' => ['name' => 'Mangala', 'years' => 1.0, 'lord' => 'moon'],
        'pingala' => ['name' => 'Pingala', 'years' => 2.0, 'lord' => 'sun'],
        'dhanya' => ['name' => 'Dhanya', 'years' => 3.0, 'lord' => 'jupiter'],
        'bhramari' => ['name' => 'Bhramari', 'years' => 4.0, 'lord' => 'mars'],
        'bhadrika' => ['name' => 'Bhadrika', 'years' => 5.0, 'lord' => 'mercury'],
        'ulka' => ['name' => 'Ulka', 'years' => 6.0, 'lord' => 'saturn'],
        'siddha' => ['name' => 'Siddha', 'years' => 7.0, 'lord' => 'venus'],
        'sankata' => ['name' => 'Sankata', 'years' => 8.0, 'lord' => 'rahu'],
    ];

    public function getSystemCode(): string
    {
        return self::SYSTEM_CODE;
    }

    public function getTotalCycleYears(): float
    {
        return self::TOTAL_CYCLE_YEARS;
    }

    public function calculate(
        float $moonSiderealLongitude,
        \DateTimeImmutable $birthDateTime,
        int $maxLevels = 1
    ): array {
        $nakshatraSpan = 360.0 / 27.0;
        $normMoon = fmod(fmod($moonSiderealLongitude, 360.0) + 360.0, 360.0);
        $nakshatraNum = (int) floor($normMoon / $nakshatraSpan) + 1; // 1 to 27
        $keys = array_keys(self::YOGINI_LORDS);

        // Classical rule: (Nakshatra Number + 3) mod 8
        $startingIndex = ($nakshatraNum + 3) % 8;
        $startingKey = $keys[$startingIndex];
        $initialYears = self::YOGINI_LORDS[$startingKey]['years'];

        $elapsedFraction = fmod($normMoon, $nakshatraSpan) / $nakshatraSpan;
        $balanceYears = $initialYears * (1.0 - $elapsedFraction);
        $balanceSeconds = (int) round($balanceYears * self::SOLAR_YEAR_SECONDS);

        $intervals = [];
        $currentStart = $birthDateTime;

        for ($cycle = 0; $cycle < 8; $cycle++) {
            $idx = ($startingIndex + $cycle) % 8;
            $key = $keys[$idx];
            $fullYears = self::YOGINI_LORDS[$key]['years'];

            $durationYears = ($cycle === 0) ? $balanceYears : $fullYears;
            $durationSeconds = ($cycle === 0) ? $balanceSeconds : (int) round($fullYears * self::SOLAR_YEAR_SECONDS);
            $currentEnd = $currentStart->modify("+{$durationSeconds} seconds");

            $intervals[] = new DashaInterval(
                system: self::SYSTEM_CODE,
                level: 1,
                lord: $key,
                startsAt: $currentStart,
                endsAt: $currentEnd,
                durationYears: $durationYears,
            );

            $currentStart = $currentEnd;
        }

        return $intervals;
    }
}

