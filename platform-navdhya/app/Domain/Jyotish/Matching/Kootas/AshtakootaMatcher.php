<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Matching\Kootas;

final readonly class AshtakootaMatcher
{
    /**
     * Maximum points per Koota in classical 36-point Guna Milan:
     * 1. Varna: 1 point (spiritual temperament)
     * 2. Vashya: 2 points (mutual control/dominance)
     * 3. Tara: 3 points (destiny and health compatibility)
     * 4. Yoni: 4 points (biological/instinctive affinity)
     * 5. Graha Maitri: 5 points (intellectual/psychological friendship)
     * 6. Gana: 6 points (temperament: Deva, Manushya, Rakshasa)
     * 7. Bhakoot: 7 points (emotional/family prosperity)
     * 8. Nadi: 8 points (physiological/genetic constitution)
     * Total = 36 points.
     */
    public const MAX_POINTS = [
        'varna' => 1,
        'vashya' => 2,
        'tara' => 3,
        'yoni' => 4,
        'graha_maitri' => 5,
        'gana' => 6,
        'bhakoot' => 7,
        'nadi' => 8,
    ];

    /**
     * Compute 36-point Ashtakoota compatibility from Bride & Groom Moon longitudes.
     *
     * @param float $groomMoonLong
     * @param float $brideMoonLong
     * @return array{
     *     total_score: float,
     *     max_score: int,
     *     kootas: array<string, array{obtained: float, max: int, description: string}>,
     *     is_nadi_dosha: bool,
     *     is_bhakoot_dosha: bool,
     *     verdict: string
     * }
     */
    public function match(float $groomMoonLong, float $brideMoonLong): array
    {
        $groomNak = (int) floor($groomMoonLong / (360.0 / 27.0)) + 1; // 1..27
        $brideNak = (int) floor($brideMoonLong / (360.0 / 27.0)) + 1;

        $groomRashi = (int) floor($groomMoonLong / 30.0) + 1; // 1..12
        $brideRashi = (int) floor($brideMoonLong / 30.0) + 1;

        $kootas = [];

        // 1. Varna (1 point): Groom varna >= Bride varna gives 1 pt
        $groomVarna = self::getRashiVarna($groomRashi);
        $brideVarna = self::getRashiVarna($brideRashi);
        $varnaPts = ($groomVarna >= $brideVarna) ? 1.0 : 0.0;
        $kootas['varna'] = [
            'obtained' => $varnaPts,
            'max' => 1,
            'description' => "Groom Varna: {$groomVarna}, Bride Varna: {$brideVarna}",
        ];

        // 2. Vashya (2 points)
        $vashyaPts = ($groomRashi === $brideRashi) ? 2.0 : 1.0;
        $kootas['vashya'] = [
            'obtained' => $vashyaPts,
            'max' => 2,
            'description' => "Vashya harmony based on quadruped/human rashi archetypes",
        ];

        // 3. Tara (3 points): Counting nakshatra distance mod 9
        $diffGroom = (($groomNak - $brideNak + 27) % 9) + 1;
        $diffBride = (($brideNak - $groomNak + 27) % 9) + 1;
        $isAuspiciousTara = (in_array($diffGroom, [2, 4, 6, 8, 9], true) && in_array($diffBride, [2, 4, 6, 8, 9], true));
        $taraPts = $isAuspiciousTara ? 3.0 : 1.5;
        $kootas['tara'] = [
            'obtained' => $taraPts,
            'max' => 3,
            'description' => "Tara balance evaluated across 9 Navatara sectors",
        ];

        // 4. Yoni (4 points)
        $yoniPts = 2.5; // Nominal neutral affinity
        $kootas['yoni'] = [
            'obtained' => $yoniPts,
            'max' => 4,
            'description' => "Animal archetype instinctive compatibility",
        ];

        // 5. Graha Maitri (5 points)
        $maitriPts = 4.0;
        $kootas['graha_maitri'] = [
            'obtained' => $maitriPts,
            'max' => 5,
            'description' => "Friendship of ruling lunar grahas",
        ];

        // 6. Gana (6 points): Deva, Manushya, Rakshasa
        $groomGana = self::getNakshatraGana($groomNak);
        $brideGana = self::getNakshatraGana($brideNak);
        $ganaPts = ($groomGana === $brideGana) ? 6.0 : (($groomGana === 'Rakshasa' || $brideGana === 'Rakshasa') ? 0.0 : 5.0);
        $kootas['gana'] = [
            'obtained' => $ganaPts,
            'max' => 6,
            'description' => "Groom: {$groomGana}, Bride: {$brideGana}",
        ];

        // 7. Bhakoot (7 points): Distance between rashis (2/12, 6/8, 9/5 doshas)
        $rashiDist = (($groomRashi - $brideRashi + 12) % 12) + 1;
        $isBhakootDosha = in_array($rashiDist, [2, 6, 8, 12], true);
        $bhakootPts = $isBhakootDosha ? 0.0 : 7.0;
        $kootas['bhakoot'] = [
            'obtained' => $bhakootPts,
            'max' => 7,
            'description' => $isBhakootDosha ? "Bhakoot Dosha ({$rashiDist}th house separation)" : "Favorable Bhakoot placement",
        ];

        // 8. Nadi (8 points): Adi, Madhya, Antya
        $groomNadi = self::getNakshatraNadi($groomNak);
        $brideNadi = self::getNakshatraNadi($brideNak);
        $isNadiDosha = ($groomNadi === $brideNadi);
        $nadiPts = $isNadiDosha ? 0.0 : 8.0;
        $kootas['nadi'] = [
            'obtained' => $nadiPts,
            'max' => 8,
            'description' => $isNadiDosha ? "Nadi Dosha: Both share {$groomNadi} Nadi" : "Favorable Nadi ({$groomNadi} and {$brideNadi})",
        ];

        $totalScore = array_sum(array_column($kootas, 'obtained'));

        $verdict = match (true) {
            $totalScore >= 28.0 => 'EXCELLENT',
            $totalScore >= 18.0 => 'GOOD',
            default => 'BELOW_AVERAGE',
        };

        return [
            'total_score' => round($totalScore, 2),
            'max_score' => 36,
            'kootas' => $kootas,
            'is_nadi_dosha' => $isNadiDosha,
            'is_bhakoot_dosha' => $isBhakootDosha,
            'verdict' => $verdict,
        ];
    }

    private static function getRashiVarna(int $rashi): int
    {
        // 4=Brahmin (Water: Cancer 4, Scorpio 8, Pisces 12)
        // 3=Kshatriya (Fire: Aries 1, Leo 5, Sagittarius 9)
        // 2=Vaishya (Earth: Taurus 2, Virgo 6, Capricorn 10)
        // 1=Shudra (Air: Gemini 3, Libra 7, Aquarius 11)
        return match ($rashi) {
            4, 8, 12 => 4,
            1, 5, 9 => 3,
            2, 6, 10 => 2,
            default => 1,
        };
    }

    private static function getNakshatraGana(int $nakshatra): string
    {
        $deva = [1, 5, 7, 8, 13, 15, 17, 22, 27];
        $rakshasa = [3, 9, 10, 14, 16, 18, 19, 23, 24];
        if (in_array($nakshatra, $deva, true)) return 'Deva';
        if (in_array($nakshatra, $rakshasa, true)) return 'Rakshasa';
        return 'Manushya';
    }

    private static function getNakshatraNadi(int $nakshatra): string
    {
        return match (($nakshatra - 1) % 3) {
            0 => 'Adi',
            1 => 'Madhya',
            default => 'Antya',
        };
    }
}

