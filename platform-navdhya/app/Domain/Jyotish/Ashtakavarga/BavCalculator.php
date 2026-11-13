<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Ashtakavarga;

use App\Domain\Jyotish\Ashtakavarga\Models\BavMatrix;

final readonly class BavCalculator
{
    /**
     * Classical Parashari Bindu placement rules for 7 planets from 8 contributors (7 planets + Lagna).
     * BPHS Ch. 66-72.
     * Table: [Contributor => Array of favorable houses relative to contributor]
     */
    public const BAV_RULES = [
        'sun' => [
            'sun' => [1, 2, 4, 7, 8, 9, 10, 11],
            'moon' => [3, 6, 10, 11],
            'mars' => [1, 2, 4, 7, 8, 9, 10, 11],
            'mercury' => [3, 5, 6, 9, 10, 11, 12],
            'jupiter' => [5, 6, 9, 11],
            'venus' => [6, 7, 12],
            'saturn' => [1, 2, 4, 7, 8, 9, 10, 11],
            'lagna' => [3, 4, 6, 10, 11, 12],
        ],
        'moon' => [
            'sun' => [3, 6, 7, 8, 10, 11],
            'moon' => [1, 3, 6, 7, 10, 11],
            'mars' => [2, 3, 5, 6, 9, 10, 11],
            'mercury' => [1, 3, 4, 5, 7, 8, 10, 11],
            'jupiter' => [1, 4, 7, 8, 10, 11, 12],
            'venus' => [3, 4, 5, 7, 9, 10, 11],
            'saturn' => [3, 5, 6, 11],
            'lagna' => [3, 6, 10, 11],
        ],
        'mars' => [
            'sun' => [3, 5, 6, 10, 11],
            'moon' => [3, 6, 11],
            'mars' => [1, 2, 4, 7, 8, 10, 11],
            'mercury' => [3, 5, 6, 11],
            'jupiter' => [6, 10, 11, 12],
            'venus' => [6, 8, 11, 12],
            'saturn' => [1, 4, 7, 8, 9, 10, 11],
            'lagna' => [1, 3, 6, 10, 11],
        ],
        'mercury' => [
            'sun' => [5, 6, 9, 11, 12],
            'moon' => [2, 4, 6, 8, 10, 11],
            'mars' => [1, 2, 4, 7, 8, 9, 10, 11],
            'mercury' => [1, 3, 5, 6, 9, 10, 11, 12],
            'jupiter' => [6, 8, 11, 12],
            'venus' => [1, 2, 3, 4, 5, 8, 9, 11],
            'saturn' => [1, 2, 4, 7, 8, 9, 10, 11],
            'lagna' => [1, 2, 4, 6, 8, 10, 11],
        ],
        'jupiter' => [
            'sun' => [1, 2, 3, 4, 7, 8, 9, 10, 11],
            'moon' => [2, 5, 7, 9, 11],
            'mars' => [1, 2, 4, 7, 8, 10, 11],
            'mercury' => [1, 2, 4, 5, 6, 9, 10, 11],
            'jupiter' => [1, 2, 3, 4, 7, 8, 10, 11],
            'venus' => [2, 5, 6, 9, 10, 11],
            'saturn' => [3, 5, 6, 12],
            'lagna' => [1, 2, 4, 5, 6, 7, 9, 10, 11],
        ],
        'venus' => [
            'sun' => [8, 11, 12],
            'moon' => [1, 2, 3, 4, 5, 8, 9, 11, 12],
            'mars' => [3, 5, 6, 9, 11, 12],
            'mercury' => [3, 5, 6, 9, 11],
            'jupiter' => [5, 8, 9, 10, 11],
            'venus' => [1, 2, 3, 4, 5, 8, 9, 10, 11],
            'saturn' => [3, 4, 5, 8, 9, 10, 11],
            'lagna' => [1, 2, 3, 4, 5, 8, 9, 11],
        ],
        'saturn' => [
            'sun' => [1, 2, 4, 7, 8, 10, 11],
            'moon' => [3, 6, 11],
            'mars' => [3, 5, 6, 10, 11, 12],
            'mercury' => [6, 8, 9, 10, 11, 12],
            'jupiter' => [5, 6, 11, 12],
            'venus' => [6, 11, 12],
            'saturn' => [3, 5, 6, 11],
            'lagna' => [1, 3, 4, 6, 10, 11],
        ],
    ];

    /**
     * Compute Bhinna Ashtakavarga for a target planet given planetary & ascendant signs.
     *
     * @param string $targetGraha
     * @param array<string, int> $positions [graha/lagna => rashi 1..12]
     * @return BavMatrix
     */
    public function calculateBav(string $targetGraha, array $positions): BavMatrix
    {
        $g = strtolower($targetGraha);
        $rules = self::BAV_RULES[$g] ?? throw new \InvalidArgumentException("No BAV rules for {$targetGraha}");

        // Initialize 12 signs with 0 bindus
        $signBindus = array_fill(1, 12, 0);
        $prastara = [];

        foreach ($rules as $contributor => $houses) {
            $contributorSign = $positions[$contributor] ?? 1;
            $prastara[$contributor] = array_fill(1, 12, 0);

            foreach ($houses as $h) {
                // Sign reached = (contributorSign + h - 2) mod 12 + 1
                $targetSign = (($contributorSign + $h - 2) % 12) + 1;
                $signBindus[$targetSign]++;
                $prastara[$contributor][$targetSign] = 1;
            }
        }

        return new BavMatrix($g, $signBindus, $prastara);
    }
}

