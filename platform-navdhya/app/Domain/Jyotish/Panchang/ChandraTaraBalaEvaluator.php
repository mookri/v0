<?php

namespace App\Domain\Jyotish\Panchang;

/**
 * Chandra Bala & Tara Bala Auspicious Strength Evaluator (T-JY05-05).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class ChandraTaraBalaEvaluator
{
    public const TARA_NAMES = [
        1 => ['name' => 'Janma', 'quality' => 'danger_or_effort', 'is_benefic' => false],
        2 => ['name' => 'Sampat', 'quality' => 'wealth_and_prosperity', 'is_benefic' => true],
        3 => ['name' => 'Vipat', 'quality' => 'calamity_and_loss', 'is_benefic' => false],
        4 => ['name' => 'Kshema', 'quality' => 'comfort_and_wellbeing', 'is_benefic' => true],
        5 => ['name' => 'Pratyak', 'quality' => 'obstacles_and_hurdles', 'is_benefic' => false],
        6 => ['name' => 'Sadhana', 'quality' => 'achievement_and_success', 'is_benefic' => true],
        7 => ['name' => 'Naidhana', 'quality' => 'danger_and_destruction', 'is_benefic' => false],
        8 => ['name' => 'Mitra', 'quality' => 'friendship_and_support', 'is_benefic' => true],
        9 => ['name' => 'Parama Mitra', 'quality' => 'great_fortune_and_ally', 'is_benefic' => true],
    ];

    /**
     * Evaluate Chandra Bala (Moon's house distance from natal Moon).
     *
     * @param int $natalRashi Rashi index (0..11) of natal Moon
     * @param int $transitRashi Rashi index (0..11) of current transit Moon
     * @return array{
     *     house_distance: int,
     *     is_auspicious: bool,
     *     classification: string,
     *     description: string
     * }
     */
    public static function evaluateChandraBala(int $natalRashi, int $transitRashi): array
    {
        $dist = (($transitRashi - $natalRashi + 12) % 12) + 1;

        $isAuspicious = in_array($dist, [1, 3, 6, 7, 10, 11], true);

        $classification = match ($dist) {
            1, 3, 6, 7, 10, 11 => 'auspicious',
            4 => 'kantaka_chandra',
            8 => 'ashtama_chandra',
            12 => 'inauspicious',
            default => 'neutral',
        };

        $desc = match ($classification) {
            'auspicious' => 'Strong Chandra Bala granting mental clarity, support and success.',
            'ashtama_chandra' => 'Ashtama Chandra (8th Moon) carrying heightened vulnerability and anxiety.',
            'kantaka_chandra' => 'Kantaka Chandra (4th Moon) creating emotional turbulence and friction.',
            'inauspicious' => '12th Moon indicating expenditure, delays and fatigue.',
            default => 'Moderate Chandra Bala with neutral outcome.',
        };

        return [
            'house_distance' => $dist,
            'is_auspicious' => $isAuspicious,
            'classification' => $classification,
            'description' => $desc,
        ];
    }

    /**
     * Evaluate Tara Bala (Transit Nakshatra distance from Natal Nakshatra).
     *
     * @param int $natalNakshatra Nakshatra index (0..26)
     * @param int $transitNakshatra Nakshatra index (0..26)
     * @return array{
     *     distance: int,
     *     tara_number: int,
     *     tara_name: string,
     *     quality: string,
     *     is_benefic: bool,
     *     cycle: int
     * }
     */
    public static function evaluateTaraBala(int $natalNakshatra, int $transitNakshatra): array
    {
        $dist = (($transitNakshatra - $natalNakshatra + 27) % 27) + 1;
        $taraNumber = (($dist - 1) % 9) + 1;
        $cycle = (int) floor(($dist - 1) / 9) + 1;

        $meta = self::TARA_NAMES[$taraNumber];

        return [
            'distance' => $dist,
            'tara_number' => $taraNumber,
            'tara_name' => $meta['name'],
            'quality' => $meta['quality'],
            'is_benefic' => $meta['is_benefic'],
            'cycle' => $cycle,
        ];
    }
}

