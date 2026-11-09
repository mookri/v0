<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Panchang calculation confidence value object (pure domain).
 * Reflects bounded honesty regarding boundary transitions within +/- 15 min fixture tolerance.
 */
final class PanchangConfidence
{
    public const HIGH = 'HIGH';
    public const MEDIUM = 'MEDIUM';
    public const LOW = 'LOW';

    /**
     * @param float $tithiProgress Fraction of tithi completed [0.0, 1.0]
     * @return string HIGH | MEDIUM | LOW
     */
    public static function forTithi(float $tithiProgress): string
    {
        // 1 tithi = 12 degrees elongation ≈ 24 hours ≈ 1440 minutes.
        // 15 minutes ≈ 0.0104 fraction; 45 minutes ≈ 0.03125 fraction.
        $distFromBoundary = min($tithiProgress, 1.0 - $tithiProgress);

        if ($distFromBoundary <= 0.011) {
            return self::LOW; // Within ~15 mins of a boundary flip
        }

        if ($distFromBoundary <= 0.032) {
            return self::MEDIUM; // Within 15-45 mins of a boundary flip
        }

        return self::HIGH;
    }
}
