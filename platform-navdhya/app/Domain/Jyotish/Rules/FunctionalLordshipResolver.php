<?php

namespace App\Domain\Jyotish\Rules;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Rules\Datasets\ParashariDignities;

/**
 * Functional Benefic/Malefic Nature & Yogakaraka Resolver (T-JY04-06, BPHS Ch. 34).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class FunctionalLordshipResolver implements ZodiacConstants
{
    /**
     * Resolve functional nature of all 7 classical planets for a given Lagna Rashi.
     *
     * @param int $lagnaRashi Rashi index of Ascendant (0..11)
     * @return array<string, array{
     *     planet: string,
     *     houses_owned: list<int>,
     *     nature: string,
     *     is_yogakaraka: bool,
     *     is_maraka: bool,
     *     classification: string
     * }>
     */
    public static function resolveForLagna(int $lagnaRashi): array
    {
        $classicalPlanets = ['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn'];
        $results = [];

        foreach ($classicalPlanets as $planet) {
            $ownedRashis = ParashariDignities::OWN_SIGNS[$planet] ?? [];
            $ownedHouses = [];

            foreach ($ownedRashis as $rashi) {
                $house = (($rashi - $lagnaRashi + 12) % 12) + 1;
                $ownedHouses[] = $house;
            }
            sort($ownedHouses);

            $hasKendra = (bool) array_intersect($ownedHouses, [4, 7, 10]);
            $hasTrikona = (bool) array_intersect($ownedHouses, [1, 5, 9]);

            // Lagna lord (H1) is both Kendra and Trikona
            $isYogakaraka = (count($ownedHouses) >= 2 && $hasKendra && $hasTrikona && ! in_array(1, $ownedHouses, true))
                || ($lagnaRashi === 3 && $planet === 'mars') // Cancer Lagna -> Mars owns 5 & 10
                || ($lagnaRashi === 4 && $planet === 'mars') // Leo Lagna -> Mars owns 4 & 9
                || ($lagnaRashi === 1 && $planet === 'saturn') // Taurus Lagna -> Saturn owns 9 & 10
                || ($lagnaRashi === 6 && $planet === 'saturn') // Libra Lagna -> Saturn owns 4 & 5
                || ($lagnaRashi === 9 && $planet === 'venus') // Capricorn Lagna -> Venus owns 5 & 10
                || ($lagnaRashi === 10 && $planet === 'venus'); // Aquarius Lagna -> Venus owns 4 & 9

            $isMaraka = in_array(2, $ownedHouses, true) || in_array(7, $ownedHouses, true);

            $hasDusthana = (bool) array_intersect($ownedHouses, [6, 8, 12]);
            $hasTrishadaya = (bool) array_intersect($ownedHouses, [3, 6, 11]);

            $nature = match (true) {
                $isYogakaraka => 'yogakaraka',
                in_array(1, $ownedHouses, true) => 'benefic',
                $hasTrikona && ! $hasDusthana => 'benefic',
                $hasDusthana && ! $hasTrikona => 'malefic',
                $hasTrishadaya => 'malefic',
                default => 'neutral',
            };

            $results[$planet] = [
                'planet' => $planet,
                'houses_owned' => $ownedHouses,
                'nature' => $nature,
                'is_yogakaraka' => $isYogakaraka,
                'is_maraka' => $isMaraka,
                'classification' => $isYogakaraka ? 'Yogakaraka (Supreme Benefic)' : ucfirst($nature),
            ];
        }

        return $results;
    }
}

