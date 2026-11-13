<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Dasha\Systems;

use App\Domain\Jyotish\Dasha\Contracts\DashaSystemInterface;
use App\Domain\Jyotish\Dasha\Models\DashaInterval;

final readonly class AshtottariCalculator implements DashaSystemInterface
{
    public const SYSTEM_CODE = 'ASHTOTTARI';
    public const TOTAL_CYCLE_YEARS = 108.0;
    public const SOLAR_YEAR_SECONDS = 365.25 * 86400.0;

    // Ashtottari 8-lord sequence (excluding Ketu in classical Parashari system)
    public const LORDS_ORDER = ['sun', 'moon', 'mars', 'mercury', 'saturn', 'jupiter', 'rahu', 'venus'];
    public const LORD_YEARS = [
        'sun' => 6.0,
        'moon' => 15.0,
        'mars' => 8.0,
        'mercury' => 17.0,
        'saturn' => 10.0,
        'jupiter' => 19.0,
        'rahu' => 12.0,
        'venus' => 21.0,
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
        $nakshatraIndex = (int) floor($normMoon / $nakshatraSpan);
        if ($nakshatraIndex > 26) $nakshatraIndex = 26;

        $elapsedFraction = ($normMoon - ($nakshatraIndex * $nakshatraSpan)) / $nakshatraSpan;
        $initialLordIndex = $nakshatraIndex % 8;
        $initialLord = self::LORDS_ORDER[$initialLordIndex];
        $initialYears = self::LORD_YEARS[$initialLord];

        $balanceYears = $initialYears * (1.0 - $elapsedFraction);
        $balanceSeconds = (int) round($balanceYears * self::SOLAR_YEAR_SECONDS);

        $intervals = [];
        $currentStart = $birthDateTime;

        for ($cycle = 0; $cycle < 8; $cycle++) {
            $lordIdx = ($initialLordIndex + $cycle) % 8;
            $lord = self::LORDS_ORDER[$lordIdx];
            $fullYears = self::LORD_YEARS[$lord];

            $durationYears = ($cycle === 0) ? $balanceYears : $fullYears;
            $durationSeconds = ($cycle === 0) ? $balanceSeconds : (int) round($fullYears * self::SOLAR_YEAR_SECONDS);
            $currentEnd = $currentStart->modify("+{$durationSeconds} seconds");

            $intervals[] = new DashaInterval(
                system: self::SYSTEM_CODE,
                level: 1,
                lord: $lord,
                startsAt: $currentStart,
                endsAt: $currentEnd,
                durationYears: $durationYears,
            );

            $currentStart = $currentEnd;
        }

        return $intervals;
    }
}

