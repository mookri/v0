<?php

namespace App\Domain\Calendar\Panchang;

use InvalidArgumentException;

/**
 * Vedic Tara Bala (Strength of Lunar Mansion) Calculator.
 *
 * Counts from devotee's Janma Nakshatra (1-27) to current transit Nakshatra (1-27)
 * through three 9-star cycles (Paryayas).
 */
final class TaraBalaCalculator
{
    public const TARAS = [
        1 => ['name' => 'JANMA', 'nature' => 'NEUTRAL_CAUTION', 'is_auspicious' => false],
        2 => ['name' => 'SAMPAT', 'nature' => 'AUSPICIOUS', 'is_auspicious' => true],
        3 => ['name' => 'VIPAT', 'nature' => 'INAUSPICIOUS', 'is_auspicious' => false],
        4 => ['name' => 'KSHEMA', 'nature' => 'AUSPICIOUS', 'is_auspicious' => true],
        5 => ['name' => 'PRATYAK', 'nature' => 'INAUSPICIOUS', 'is_auspicious' => false],
        6 => ['name' => 'SADHAKA', 'nature' => 'AUSPICIOUS', 'is_auspicious' => true],
        7 => ['name' => 'NAIDHANA', 'nature' => 'INAUSPICIOUS', 'is_auspicious' => false],
        8 => ['name' => 'MITRA', 'nature' => 'AUSPICIOUS', 'is_auspicious' => true],
        9 => ['name' => 'PARAMA_MITRA', 'nature' => 'AUSPICIOUS', 'is_auspicious' => true],
    ];

    /**
     * @param int $janmaNakshatra Devotee birth nakshatra [1, 27]
     * @param int $transitNakshatra Current day nakshatra [1, 27]
     * @return array{
     *     tara_number: int,
     *     tara_name: string,
     *     nature: string,
     *     is_auspicious: bool,
     *     cycle: int
     * }
     */
    public static function calculate(int $janmaNakshatra, int $transitNakshatra): array
    {
        if ($janmaNakshatra < 1 || $janmaNakshatra > 27 || $transitNakshatra < 1 || $transitNakshatra > 27) {
            throw new InvalidArgumentException('Nakshatra numbers must be in range 1-27.');
        }

        // Distance from birth nakshatra (1-indexed)
        $distance = (($transitNakshatra - $janmaNakshatra + 27) % 27) + 1;

        // 9-star cycle slot (1 to 9)
        $taraNumber = (($distance - 1) % 9) + 1;
        $cycle = (int) floor(($distance - 1) / 9) + 1; // 1 = Prathama, 2 = Dvitiya, 3 = Tritiya

        $meta = self::TARAS[$taraNumber];

        return [
            'tara_number' => $taraNumber,
            'tara_name' => $meta['name'],
            'nature' => $meta['nature'],
            'is_auspicious' => $meta['is_auspicious'],
            'cycle' => $cycle,
        ];
    }
}

