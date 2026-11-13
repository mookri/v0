<?php

namespace App\Domain\Calendar\Panchang;

use InvalidArgumentException;

/**
 * Vedic Chandra Bala (Lunar Strength by Rashi Transit) Calculator.
 *
 * Counts house distance from devotee's Janma Rashi (1-12) to current Moon Rashi (1-12).
 * Houses 1, 3, 6, 7, 10, 11 are auspicious.
 * House 8 is Ashtama Chandra (inauspicious, avoided for vital endeavors).
 */
final class ChandraBalaCalculator
{
    public const AUSPICIOUS_HOUSES = [1, 3, 6, 7, 10, 11];

    /**
     * @param int $janmaRashi Birth Moon sign [1, 12]
     * @param int $transitRashi Current transit Moon sign [1, 12]
     * @return array{
     *     house: int,
     *     is_auspicious: bool,
     *     is_ashtama_chandra: bool,
     *     description: string
     * }
     */
    public static function calculate(int $janmaRashi, int $transitRashi): array
    {
        if ($janmaRashi < 1 || $janmaRashi > 12 || $transitRashi < 1 || $transitRashi > 12) {
            throw new InvalidArgumentException('Rashi numbers must be in range 1-12.');
        }

        $house = (($transitRashi - $janmaRashi + 12) % 12) + 1;
        $isAuspicious = in_array($house, self::AUSPICIOUS_HOUSES, true);
        $isAshtama = $house === 8;

        $desc = match (true) {
            $isAshtama => 'Ashtama Chandra (8th house): highly inauspicious for major vows or commencement.',
            $isAuspicious => "Auspicious Chandra Bala ({$house}th house from Janma Rashi).",
            default => "Moderate/Unfavorable Chandra Bala ({$house}th house from Janma Rashi).",
        };

        return [
            'house' => $house,
            'is_auspicious' => $isAuspicious,
            'is_ashtama_chandra' => $isAshtama,
            'description' => $desc,
        ];
    }
}

