<?php

namespace App\Domain\Calendar\Astronomy;

use Carbon\CarbonImmutable;

/**
 * Terrestrial Time (TT) minus Universal Time (UT) polynomial calculator.
 * Implements NASA / Espenak-Meeus polynomial approximations piecewise by era.
 *
 * ΔT = TT - UT
 */
final class DeltaT
{
    /**
     * Compute ΔT in seconds for a given decimal year.
     */
    public static function inSeconds(float $year): float
    {
        // Modern era: 2005 - 2050
        if ($year >= 2005.0 && $year <= 2050.0) {
            $t = $year - 2000.0;
            return 62.92 + (0.32217 * $t) + (0.005589 * $t * $t);
        }

        // 1986 - 2005
        if ($year >= 1986.0 && $year < 2005.0) {
            $t = $year - 2000.0;
            return 63.86 + (0.3345 * $t) - (0.060374 * $t * $t) + (0.0017275 * $t * $t * $t);
        }

        // Extrapolation default for modern era
        $t = $year - 2000.0;
        return 62.92 + (0.32217 * $t) + (0.005589 * $t * $t);
    }

    public static function forDate(CarbonImmutable $utc): float
    {
        $decimalYear = $utc->year + (($utc->dayOfYear - 1) / ($utc->isLeapYear() ? 366.0 : 365.0));
        return self::inSeconds($decimalYear);
    }

    /**
     * Convert UTC instant to Terrestrial Time (TT).
     */
    public static function toTerrestrialTime(CarbonImmutable $utc): CarbonImmutable
    {
        $deltaTSeconds = self::forDate($utc);
        $deltaTMicroseconds = (int) round($deltaTSeconds * 1_000_000);

        return $utc->addMicroseconds($deltaTMicroseconds);
    }
}

