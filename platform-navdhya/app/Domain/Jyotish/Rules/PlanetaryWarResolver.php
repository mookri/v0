<?php

namespace App\Domain\Jyotish\Rules;

use App\Domain\Jyotish\Support\Angle;

/**
 * Planetary War (Graha Yuddha) Engine (T-JY04-03).
 *
 * Implements classical Graha Yuddha conflict resolution per Surya Siddhanta Ch. 7 & Brihat Samhita Ch. 17.
 * Governs the 5 true tara grahas: Mars, Mercury, Jupiter, Venus, Saturn.
 * War occurs when separation < 1.0°.
 *
 * Rules:
 * 1. Venus always prevails (Bhrigu Jayam).
 * 2. Otherwise, the planet situated to the North (higher celestial latitude β) wins.
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class PlanetaryWarResolver
{
    public const WAR_ORB_DEGREES = 1.0;

    public const TARA_GRAHAS = ['mars', 'mercury', 'jupiter', 'venus', 'saturn'];

    /**
     * Detect and resolve all active planetary wars.
     *
     * @param array<string, array{longitude: float, latitude: float}> $planetaryPositions
     * @return list<array{
     *     planet_a: string,
     *     planet_b: string,
     *     separation_degrees: float,
     *     winner: string,
     *     loser: string,
     *     victory_reason: string
     * }>
     */
    public static function resolveAll(array $planetaryPositions): array
    {
        $wars = [];
        $grahas = array_intersect(self::TARA_GRAHAS, array_keys($planetaryPositions));

        $count = count($grahas);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $p1 = $grahas[$i];
                $p2 = $grahas[$j];

                $pos1 = $planetaryPositions[$p1];
                $pos2 = $planetaryPositions[$p2];

                $sep = abs(Angle::normalize($pos1['longitude']) - Angle::normalize($pos2['longitude']));
                if ($sep > 180.0) {
                    $sep = 360.0 - $sep;
                }

                if ($sep < self::WAR_ORB_DEGREES) {
                    $wars[] = self::resolvePair($p1, $pos1, $p2, $pos2, $sep);
                }
            }
        }

        return $wars;
    }

    /**
     * Resolve a single planetary war between two planets.
     *
     * @param array{longitude: float, latitude: float} $posA
     * @param array{longitude: float, latitude: float} $posB
     * @return array{
     *     planet_a: string,
     *     planet_b: string,
     *     separation_degrees: float,
     *     winner: string,
     *     loser: string,
     *     victory_reason: string
     * }
     */
    public static function resolvePair(
        string $planetA,
        array $posA,
        string $planetB,
        array $posB,
        ?float $separation = null
    ): array {
        $sep = $separation ?? abs(Angle::normalize($posA['longitude']) - Angle::normalize($posB['longitude']));
        if ($sep > 180.0) {
            $sep = 360.0 - $sep;
        }

        // Rule 1: Venus always triumphs (Bhrigu Jayam)
        if ($planetA === 'venus') {
            return [
                'planet_a' => $planetA,
                'planet_b' => $planetB,
                'separation_degrees' => round($sep, 4),
                'winner' => $planetA,
                'loser' => $planetB,
                'victory_reason' => 'Venus radiant superiority (Bhrigu Jayam)',
            ];
        }

        if ($planetB === 'venus') {
            return [
                'planet_a' => $planetA,
                'planet_b' => $planetB,
                'separation_degrees' => round($sep, 4),
                'winner' => $planetB,
                'loser' => $planetA,
                'victory_reason' => 'Venus radiant superiority (Bhrigu Jayam)',
            ];
        }

        // Rule 2: Northern celestial latitude (Uttarastha) triumphs
        $latA = $posA['latitude'];
        $latB = $posB['latitude'];

        if ($latA >= $latB) {
            $winner = $planetA;
            $loser = $planetB;
        } else {
            $winner = $planetB;
            $loser = $planetA;
        }

        return [
            'planet_a' => $planetA,
            'planet_b' => $planetB,
            'separation_degrees' => round($sep, 4),
            'winner' => $winner,
            'loser' => $loser,
            'victory_reason' => 'Northern celestial latitude victory (Uttarastha)',
        ];
    }
}

