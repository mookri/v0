<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Jaimini;

use App\Domain\Jyotish\Jaimini\ArudhaLagnaCalculator;
use App\Domain\Jyotish\Jaimini\CharaKarakaCalculator;

final readonly class JaiminiEngine
{
    public function __construct(
        private CharaKarakaCalculator $karakaCalc = new CharaKarakaCalculator(),
    ) {
    }

    /**
     * Compute full Jaimini aggregate (7 & 8 Karakas, Arudha Lagna, Upapada Lagna, Karakamsha).
     *
     * @param array<string, float> $planetLongitudes
     * @param int $lagnaRashi
     * @param array<int, int> $houseLords [house 1..12 => lord sign 1..12]
     * @param int $atmakarakaNavamshaSign
     * @return array<string, mixed>
     */
    public function calculate(
        array $planetLongitudes,
        int $lagnaRashi,
        array $houseLords,
        int $atmakarakaNavamshaSign = 1
    ): array {
        $karakas7 = $this->karakaCalc->calculate($planetLongitudes, 7);
        $karakas8 = $this->karakaCalc->calculate($planetLongitudes, 8);

        // Arudha Lagna (AL - House 1)
        $lagnaLordSign = $houseLords[1] ?? $lagnaRashi;
        $arudhaLagna = ArudhaLagnaCalculator::calculatePada(1, $lagnaRashi, $lagnaLordSign);

        // Upapada Lagna (UL - House 12)
        $house12Sign = (($lagnaRashi + 12 - 2) % 12) + 1; // 12th from Lagna
        $lord12Sign = $houseLords[12] ?? $house12Sign;
        $upapadaLagna = ArudhaLagnaCalculator::calculatePada(12, $house12Sign, $lord12Sign);

        // All 12 Arudha Padas (A1 to A12)
        $allPadas = [];
        for ($h = 1; $h <= 12; $h++) {
            $hSign = (($lagnaRashi + $h - 2) % 12) + 1;
            $lordSign = $houseLords[$h] ?? $hSign;
            $allPadas["A{$h}"] = ArudhaLagnaCalculator::calculatePada($h, $hSign, $lordSign);
        }

        return [
            'karakas_7' => $karakas7,
            'karakas_8' => $karakas8,
            'atmakaraka' => $karakas7['AK']['graha'] ?? 'sun',
            'karakamsha_sign' => $atmakarakaNavamshaSign,
            'arudha_lagna' => $arudhaLagna,
            'upapada_lagna' => $upapadaLagna,
            'arudha_padas' => $allPadas,
        ];
    }
}

