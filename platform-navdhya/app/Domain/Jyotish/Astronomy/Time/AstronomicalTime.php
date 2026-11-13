<?php

namespace App\Domain\Jyotish\Astronomy\Time;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Astronomical Time Calculator & Aggregator (T-JY01-03).
 *
 * Implements rigorous time-scale conversions between UTC, UT1, and TT using
 * NASA / Espenak-Meeus polynomials for Delta T = TT - UT.
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class AstronomicalTime
{
    public function __construct(
        public JdUtc $utc,
        public JdUt1 $ut1,
        public JdTt $tt,
        public float $deltaTSeconds,
    ) {
    }

    /**
     * Build an AstronomicalTime instance from a UTC DateTimeImmutable.
     */
    public static function fromUtcDateTime(DateTimeImmutable $utc): self
    {
        if ($utc->getTimezone()->getName() !== 'UTC' && $utc->getOffset() !== 0) {
            throw new InvalidArgumentException('DateTimeImmutable must be in UTC timezone.');
        }

        $year = (int) $utc->format('Y');
        $month = (int) $utc->format('n');
        $day = (int) $utc->format('j');
        $hour = (int) $utc->format('G');
        $minute = (int) $utc->format('i');
        $second = (float) $utc->format('s') + ((float) $utc->format('u') / 1_000_000.0);

        return self::fromUtc($year, $month, $day, $hour, $minute, $second);
    }

    /**
     * Build an AstronomicalTime instance from Gregorian calendar UTC components.
     */
    public static function fromUtc(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        float $second = 0.0
    ): self {
        $jdUtcValue = self::calculateJulianDay($year, $month, $day, $hour, $minute, $second);

        $decimalYear = $year + (($month - 1) / 12.0) + ($day / 365.25);
        $deltaT = self::calculateDeltaTSeconds($decimalYear);

        $jdTtValue = $jdUtcValue + ($deltaT / 86400.0);
        $jdUt1Value = $jdUtcValue; // For sub-arcsecond work |UT1 - UTC| < 0.9s

        return new self(
            utc: new JdUtc($jdUtcValue),
            ut1: new JdUt1($jdUt1Value),
            tt: new JdTt($jdTtValue),
            deltaTSeconds: $deltaT,
        );
    }

    /**
     * Algorithm from Jean Meeus, Astronomical Algorithms, Chapter 7.
     */
    public static function calculateJulianDay(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        float $second = 0.0
    ): float {
        $y = $year;
        $m = $month;

        if ($m <= 2) {
            $y -= 1;
            $m += 12;
        }

        $a = (int) floor($y / 100);
        $b = 2 - $a + (int) floor($a / 4);

        $fractionalDay = $day + ($hour / 24.0) + ($minute / 1440.0) + ($second / 86400.0);

        return floor(365.25 * ($y + 4716))
            + floor(30.6001 * ($m + 1))
            + $fractionalDay
            + $b
            - 1524.5;
    }

    /**
     * Compute Delta T = TT - UT in seconds via Espenak-Meeus polynomial approximations.
     */
    public static function calculateDeltaTSeconds(float $year): float
    {
        // 2005 - 2050
        if ($year >= 2005.0 && $year <= 2050.0) {
            $t = $year - 2000.0;
            return 62.92 + (0.32217 * $t) + (0.005589 * $t * $t);
        }

        // 1986 - 2005
        if ($year >= 1986.0 && $year < 2005.0) {
            $t = $year - 2000.0;
            return 63.86 + (0.3345 * $t) - (0.060374 * $t * $t) + (0.0017275 * $t * $t * $t);
        }

        // 1950 - 1986
        if ($year >= 1950.0 && $year < 1986.0) {
            $t = $year - 1950.0;
            return 29.07 + (0.407 * $t) - ($t * $t / 233.0) + ($t * $t * $t / 2547.0);
        }

        // 1900 - 1950
        if ($year >= 1900.0 && $year < 1950.0) {
            $t = $year - 1900.0;
            return -2.79 + (1.494119 * $t) - (0.0598939 * $t * $t) + (0.0061966 * $t * $t * $t) - (0.000197 * $t * $t * $t * $t);
        }

        // Future extrapolation 2050 - 2150
        if ($year > 2050.0) {
            $t = ($year - 1820.0) / 100.0;
            return -20.0 + (32.0 * $t * $t);
        }

        // Pre-1900 fallback
        $t = ($year - 1820.0) / 100.0;
        return -20.0 + (32.0 * $t * $t);
    }
}

