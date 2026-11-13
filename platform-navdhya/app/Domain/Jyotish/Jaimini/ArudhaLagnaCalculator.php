<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Jaimini;

final readonly class ArudhaLagnaCalculator
{
    /**
     * Compute Arudha Pada for a given house (1..12).
     * Rule: Count houses from house to its lord (N).
     * Arudha is N houses ahead of the lord.
     * Classical Jaimini Exceptions (Upadesha Sutra 1.1.30-31):
     * 1. If Arudha falls in the house itself, Pada moves to the 10th house from it.
     * 2. If Arudha falls in the 7th from the house, Pada moves to the 10th from the 7th (i.e. 4th from original house).
     *
     * @param int $houseNumber Target house (1 = Lagna => Arudha Lagna AL, 12 => Upapada UL)
     * @param int $houseRashi Sign occupying the house (1..12)
     * @param int $lordRashi Sign occupied by the house's ruling lord (1..12)
     * @return int Arudha sign (1..12)
     */
    public static function calculatePada(int $houseNumber, int $houseRashi, int $lordRashi): int
    {
        // Distance N from house sign to lord sign (1-based count)
        $distance = (($lordRashi - $houseRashi + 12) % 12) + 1;

        // Raw Pada sign: N steps from lord sign
        $rawPadaSign = (($lordRashi + $distance - 2) % 12) + 1;

        // Relative position of raw pada from original house sign
        $relFromHouse = (($rawPadaSign - $houseRashi + 12) % 12) + 1;

        // Exception 1: Pada falls in original house (1st) => move to 10th from original house
        if ($relFromHouse === 1) {
            return (($houseRashi + 10 - 2) % 12) + 1;
        }

        // Exception 2: Pada falls in 7th from original house => move to 4th from original house (10th from 7th)
        if ($relFromHouse === 7) {
            return (($houseRashi + 4 - 2) % 12) + 1;
        }

        return $rawPadaSign;
    }
}

