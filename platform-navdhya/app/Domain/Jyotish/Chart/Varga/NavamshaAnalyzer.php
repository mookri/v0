<?php

namespace App\Domain\Jyotish\Chart\Varga;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;

/**
 * Deep D9 Navamsha Analyzer (T-JY04-08).
 *
 * Implements:
 * - Vargottama detection (identical sign in D1 and D9)
 * - Pushkara Navamsha detection (most auspicious life-affirming degrees)
 * - Navamsha Lagna and relative bhava mapping
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class NavamshaAnalyzer implements ZodiacConstants
{
    /**
     * Pushkara Navamsha Pada index (0-indexed 0..8) per elemental triplicity.
     */
    private const PUSHKARA_NAVAMSHA_PADAS = [
        0 => [6, 8], // Fire signs (Mesha, Simha, Dhanu): 7th (index 6) & 9th (index 8) padas
        1 => [2, 4], // Earth signs (Vrishabha, Kanya, Makara): 3rd (index 2) & 5th (index 4) padas
        2 => [5, 7], // Air signs (Mithuna, Tula, Kumbha): 6th (index 5) & 8th (index 7) padas
        3 => [0, 2], // Water signs (Karkataka, Vrishchika, Meena): 1st (index 0) & 3rd (index 2) padas
    ];

    /**
     * Analyze D9 Navamsha chart and deep indicators.
     *
     * @param float $lagnaDegrees Sidereal Ascendant degree
     * @param array<string, float> $planetaryDegrees Map of planet => sidereal degree
     * @return array{
     *     vargottama_planets: list<string>,
     *     pushkara_navamshas: list<string>,
     *     navamsha_lagna: array{rashi_index: int, rashi_name: string, rashi_lord: string},
     *     placements: array<string, array{
     *         planet: string,
     *         d1_rashi: int,
     *         d9_rashi: int,
     *         is_vargottama: bool,
     *         is_pushkara: bool,
     *         d9_bhava: int
     *     }>
     * }
     */
    public static function analyze(float $lagnaDegrees, array $planetaryDegrees): array
    {
        $d9Lagna = VargaHarmonicEngine::calculate('D9', $lagnaDegrees);
        $d9LagnaRashi = $d9Lagna['rashi_index'];

        $vargottama = [];
        $pushkara = [];
        $placements = [];

        foreach ($planetaryDegrees as $planet => $deg) {
            $norm = Angle::normalize($deg);
            $d1Rashi = (int) floor($norm / self::RASHI_SPAN_DEGREES);
            if ($d1Rashi >= 12) {
                $d1Rashi = 11;
            }
            $degInRashi = $norm - ($d1Rashi * self::RASHI_SPAN_DEGREES);

            $d9 = VargaHarmonicEngine::calculate('D9', $norm);
            $d9Rashi = $d9['rashi_index'];

            $isVargottama = ($d1Rashi === $d9Rashi);
            if ($isVargottama) {
                $vargottama[] = $planet;
            }

            // Pushkara Navamsha check
            $elementTriplicity = $d1Rashi % 4; // 0=Fire, 1=Earth, 2=Air, 3=Water
            $navamshaPadaInSign = (int) floor(($degInRashi + 1e-11) / (10.0 / 3.0));
            $isPushkara = in_array($navamshaPadaInSign, self::PUSHKARA_NAVAMSHA_PADAS[$elementTriplicity], true);
            if ($isPushkara) {
                $pushkara[] = $planet;
            }

            // Bhava in D9 relative to D9 Lagna
            $d9Bhava = (($d9Rashi - $d9LagnaRashi + 12) % 12) + 1;

            $placements[$planet] = [
                'planet' => $planet,
                'd1_rashi' => $d1Rashi,
                'd9_rashi' => $d9Rashi,
                'is_vargottama' => $isVargottama,
                'is_pushkara' => $isPushkara,
                'd9_bhava' => $d9Bhava,
            ];
        }

        return [
            'vargottama_planets' => $vargottama,
            'pushkara_navamshas' => $pushkara,
            'navamsha_lagna' => [
                'rashi_index' => $d9LagnaRashi,
                'rashi_name' => $d9Lagna['rashi_name'],
                'rashi_lord' => $d9Lagna['rashi_lord'],
            ],
            'placements' => $placements,
        ];
    }
}

