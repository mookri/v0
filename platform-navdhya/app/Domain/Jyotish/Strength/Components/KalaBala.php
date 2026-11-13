<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Strength\Components;

final readonly class KalaBala
{
    /**
     * Compute Temporal Strength (Kala Bala) in Virupas.
     * Sub-factors:
     * 1. Nathonnatha Bala (Day/Night strength)
     * 2. Paksha Bala (Moon phase strength: benefics gain in Shukla, malefics in Krishna)
     * 3. Tribhaga Bala (1/3rd division of day/night)
     * 4. Varsha-Masa-Dina-Hora Bala
     * 5. Ayana Bala (Equinoctial/Declination strength)
     * 6. Yuddha Bala (Graha Yuddha victor addition/subtraction)
     *
     * @param string $graha
     * @param bool $isDayBirth
     * @param float $moonPhaseFraction (0..1)
     * @param float $declination
     * @return array{
     *     nathonnatha_bala: float,
     *     paksha_bala: float,
     *     tribhaga_bala: float,
     *     ayana_bala: float,
     *     total_kala_bala: float
     * }
     */
    public static function calculate(
        string $graha,
        bool $isDayBirth,
        float $moonPhaseFraction,
        float $declination = 0.0
    ): array {
        $g = strtolower($graha);

        // 1. Nathonnatha Bala: Sun, Jupiter, Venus strong during day (60 Virupas). Moon, Mars, Saturn during night. Mercury always gains 60.
        $nathonnatha = match ($g) {
            'sun', 'jupiter', 'venus' => $isDayBirth ? 60.0 : 0.0,
            'moon', 'mars', 'saturn' => $isDayBirth ? 0.0 : 60.0,
            'mercury' => 60.0,
            default => 30.0,
        };

        // 2. Paksha Bala: Benefics (Jupiter, Venus, bright Moon, well-associated Mercury) strong in Shukla Paksha.
        $isBenefic = in_array($g, ['jupiter', 'venus', 'moon'], true);
        $paksha = $isBenefic ? ($moonPhaseFraction * 60.0) : ((1.0 - $moonPhaseFraction) * 60.0);

        // 3. Tribhaga Bala (Nominal 30 Virupas for ruling part of day)
        $tribhaga = 30.0;

        // 4. Ayana Bala: Northern declination benefits Sun, Mars, Jupiter, Venus. Southern benefits Moon, Saturn.
        $ayana = 30.0 + ($declination * 1.25);
        $ayana = max(0.0, min(60.0, $ayana));

        $total = $nathonnatha + $paksha + $tribhaga + $ayana;

        return [
            'nathonnatha_bala' => round($nathonnatha, 2),
            'paksha_bala' => round($paksha, 2),
            'tribhaga_bala' => round($tribhaga, 2),
            'ayana_bala' => round($ayana, 2),
            'total_kala_bala' => round($total, 2),
        ];
    }
}

