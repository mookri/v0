<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Strength\Components;

final readonly class CheshtaBala
{
    /**
     * Compute Motional Strength (Cheshta Bala) in Virupas (0..60).
     * Retrograde (Vakra) planets gain maximum Cheshta Bala (60 Virupas).
     * Stationary (Vikala) planets gain ~15-30 Virupas.
     * Direct (Chara/Mandara) planets gain proportional to relative speed.
     * Note: Sun and Moon do not have Cheshta Bala (Sun's Ayana Bala substitutes it; Moon's Paksha Bala substitutes it).
     *
     * @param string $graha
     * @param bool $isRetrograde
     * @param bool $isStationary
     * @param float $dailyMotionRatio (actual daily speed / average speed)
     * @return float Virupas (0..60)
     */
    public static function calculate(
        string $graha,
        bool $isRetrograde,
        bool $isStationary,
        float $dailyMotionRatio = 1.0
    ): float {
        $g = strtolower($graha);
        if ($g === 'sun' || $g === 'moon') {
            return 0.0; // Handled via Ayana/Paksha bala per classical BPHS rules
        }

        if ($isRetrograde) {
            return 60.0;
        }

        if ($isStationary) {
            return 20.0;
        }

        // Direct motion: average 30 Virupas, scaled by motion ratio
        $bala = 30.0 * min(2.0, max(0.2, $dailyMotionRatio));
        return round(max(0.0, min(60.0, $bala)), 2);
    }
}

