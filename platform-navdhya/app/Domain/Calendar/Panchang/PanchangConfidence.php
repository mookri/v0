<?php

namespace App\Domain\Calendar\Panchang;

/**
 * Astronomical calculation confidence scoring.
 * Flags calculations performed dangerously close to Tithi transition boundaries
 * where atmospheric refraction or limb differences could shift observance.
 */
final class PanchangConfidence
{
    public const HIGH = 'HIGH';
    public const MEDIUM = 'MEDIUM';
    public const LOW = 'LOW';

    /**
     * @param float $tithiProgress Fraction of tithi elapsed [0.0, 1.0]
     * @return string HIGH | MEDIUM | LOW
     */
    public static function forTithi(float $tithiProgress): string
    {
        // If within 1% of boundary (either start or end of tithi), confidence is LOW
        if ($tithiProgress < 0.01 || $tithiProgress > 0.99) {
            return self::LOW;
        }

        // If within 5% of boundary, confidence is MEDIUM
        if ($tithiProgress < 0.05 || $tithiProgress > 0.95) {
            return self::MEDIUM;
        }

        return self::HIGH;
    }
}

