<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Support;

/**
 * High-Precision Integer Microdegree Arithmetic (T-JYOPT-11 / AC-T-JYOPT-11).
 *
 * Eliminates floating-point rounding errors at boundary thresholds ($29^\circ 59' 59.999''$).
 * Scale: $1^\circ = 3,600,000\ \mu^\circ$ (microdegrees).
 * Full circle ($360^\circ$) = $1,296,000,000\ \mu^\circ$.
 * One Rashi ($30^\circ$) = $108,000,000\ \mu^\circ$.
 * One Nakshatra ($13^\circ 20' = 13.333333^\circ$) = $48,000,000\ \mu^\circ$.
 * One Pada ($3^\circ 20' = 3.333333^\circ$) = $12,000,000\ \mu^\circ$.
 */
final readonly class Microdegrees
{
    public const SCALE = 3_600_000;
    public const FULL_CIRCLE = 360 * self::SCALE; // 1,296,000,000
    public const RASHI_SPAN = 30 * self::SCALE;    // 108,000,000
    public const NAKSHATRA_SPAN = 48_000_000;      // 13° 20' = 800' = 48,000,000
    public const PADA_SPAN = 12_000_000;           // 3° 20' = 200' = 12,000,000

    /**
     * Convert float degrees to integer microdegrees.
     */
    public static function fromDegrees(float $degrees): int
    {
        $normalized = fmod(fmod($degrees, 360.0) + 360.0, 360.0);
        return (int) round($normalized * self::SCALE);
    }

    /**
     * Convert integer microdegrees back to float degrees.
     */
    public static function toDegrees(int $microdegrees): float
    {
        $norm = ($microdegrees % self::FULL_CIRCLE + self::FULL_CIRCLE) % self::FULL_CIRCLE;
        return (float) $norm / self::SCALE;
    }

    /**
     * Resolve Rashi index (0..11) with integer precision at sign borders.
     */
    public static function rashiIndex(int $microdegrees): int
    {
        $norm = ($microdegrees % self::FULL_CIRCLE + self::FULL_CIRCLE) % self::FULL_CIRCLE;
        return (int) intdiv($norm, self::RASHI_SPAN);
    }

    /**
     * Resolve Nakshatra index (0..26) with integer precision.
     */
    public static function nakshatraIndex(int $microdegrees): int
    {
        $norm = ($microdegrees % self::FULL_CIRCLE + self::FULL_CIRCLE) % self::FULL_CIRCLE;
        return (int) intdiv($norm, self::NAKSHATRA_SPAN);
    }

    /**
     * Resolve Pada index (1..4) within Nakshatra.
     */
    public static function pada(int $microdegrees): int
    {
        $norm = ($microdegrees % self::FULL_CIRCLE + self::FULL_CIRCLE) % self::FULL_CIRCLE;
        $padaIndex = (int) intdiv($norm % self::NAKSHATRA_SPAN, self::PADA_SPAN);
        return $padaIndex + 1;
    }
}

