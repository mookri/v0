<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Dasha;

use App\Domain\Jyotish\Dasha\Contracts\DashaSystemInterface;
use App\Domain\Jyotish\Dasha\Models\DashaInterval;

final readonly class VimshottariCalculator implements DashaSystemInterface
{
    public const SYSTEM_CODE = 'VIMSHOTTARI';
    public const TOTAL_CYCLE_YEARS = 120.0;
    public const SOLAR_YEAR_DAYS = 365.25;
    public const SOLAR_YEAR_SECONDS = 365.25 * 86400.0;

    /**
     * Classical 9 Graha sequence and Vimshottari durations (Years). Total = 120 years.
     * Sequence starts with Ketu for Ashwini (Nakshatra 1).
     */
    public const LORDS_ORDER = ['ketu', 'venus', 'sun', 'moon', 'mars', 'rahu', 'jupiter', 'saturn', 'mercury'];
    public const LORD_YEARS = [
        'ketu' => 7.0,
        'venus' => 20.0,
        'sun' => 6.0,
        'moon' => 10.0,
        'mars' => 7.0,
        'rahu' => 18.0,
        'jupiter' => 16.0,
        'saturn' => 19.0,
        'mercury' => 17.0,
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
        int $maxLevels = 2
    ): array {
        // 1. Identify Nakshatra index (0..26) and elapsed fraction
        $nakshatraSpan = 360.0 / 27.0; // 13° 20' = 13.333333333333334°
        $normMoon = fmod(fmod($moonSiderealLongitude, 360.0) + 360.0, 360.0);
        $nakshatraIndex = (int) floor($normMoon / $nakshatraSpan);
        if ($nakshatraIndex > 26) {
            $nakshatraIndex = 26;
        }

        $elapsedInNakshatra = $normMoon - ($nakshatraIndex * $nakshatraSpan);
        $elapsedFraction = $elapsedInNakshatra / $nakshatraSpan; // p
        $balanceFraction = 1.0 - $elapsedFraction;             // (1 - p)

        // 2. Lord at birth: Nakshatra index modulo 9
        $initialLordIndex = $nakshatraIndex % 9;
        $initialLord = self::LORDS_ORDER[$initialLordIndex];
        $initialTotalYears = self::LORD_YEARS[$initialLord];

        // Initial Mahadasha balance: D = Y_lord * (1 - p)
        $balanceYears = $initialTotalYears * $balanceFraction;
        $balanceSeconds = (int) round($balanceYears * self::SOLAR_YEAR_SECONDS);

        $mahadashas = [];
        $currentStart = $birthDateTime;

        for ($cycle = 0; $cycle < 9; $cycle++) {
            $lordIdx = ($initialLordIndex + $cycle) % 9;
            $lord = self::LORDS_ORDER[$lordIdx];
            $fullYears = self::LORD_YEARS[$lord];

            if ($cycle === 0) {
                // First period is the remaining balance
                $durationYears = $balanceYears;
                $durationSeconds = $balanceSeconds;
            } else {
                $durationYears = $fullYears;
                $durationSeconds = (int) round($fullYears * self::SOLAR_YEAR_SECONDS);
            }

            $currentEnd = $currentStart->modify("+{$durationSeconds} seconds");

            // Sub-periods (Antardashas) if maxLevels >= 2
            $antardashas = [];
            if ($maxLevels >= 2) {
                $antardashas = $this->calculateAntardashas($lordIdx, $currentStart, $durationYears, $fullYears);
            }

            $mahadashas[] = new DashaInterval(
                system: self::SYSTEM_CODE,
                level: 1,
                lord: $lord,
                startsAt: $currentStart,
                endsAt: $currentEnd,
                durationYears: $durationYears,
                subPeriods: $antardashas,
            );

            $currentStart = $currentEnd;
        }

        return $mahadashas;
    }

    /**
     * @return list<DashaInterval>
     */
    private function calculateAntardashas(
        int $mahaLordIndex,
        \DateTimeImmutable $mahaStart,
        float $actualMahaYears,
        float $fullMahaYears
    ): array {
        $antars = [];
        $currentStart = $mahaStart;
        $ratio = $actualMahaYears / $fullMahaYears;

        for ($sub = 0; $sub < 9; $sub++) {
            $subLordIdx = ($mahaLordIndex + $sub) % 9;
            $subLord = self::LORDS_ORDER[$subLordIdx];
            $subFullYears = self::LORD_YEARS[$subLord];

            // Antardasha duration formula: (Maha_years * Antar_years) / 120.0
            $subDurationYears = ($fullMahaYears * $subFullYears / 120.0) * $ratio;
            $subDurationSeconds = (int) round($subDurationYears * self::SOLAR_YEAR_SECONDS);

            $currentEnd = $currentStart->modify("+{$subDurationSeconds} seconds");

            $antars[] = new DashaInterval(
                system: self::SYSTEM_CODE,
                level: 2,
                lord: $subLord,
                startsAt: $currentStart,
                endsAt: $currentEnd,
                durationYears: $subDurationYears,
            );

            $currentStart = $currentEnd;
        }

        return $antars;
    }
}

