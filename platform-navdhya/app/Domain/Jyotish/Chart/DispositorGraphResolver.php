<?php

namespace App\Domain\Jyotish\Chart;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Rules\Datasets\ParashariDignities;

/**
 * Planetary Dispositor Graph & Parivartana Yoga Resolver (T-JY04-06).
 *
 * Resolves:
 * - Direct graph of planet -> dispositor (sign lord)
 * - Self-dispositors (planets in own sign)
 * - Mutual receptions (Parivartana Yogas: Maha, Dainya, Khala)
 * - Final dispositors (root nodes of the dispositor tree)
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class DispositorGraphResolver implements ZodiacConstants
{
    /**
     * Resolve dispositors and mutual receptions from planetary rashis.
     *
     * @param array<string, int> $planetaryRashis Map of planet name => rashi index (0..11)
     * @param int $lagnaRashi Rashi index of Ascendant
     * @return array{
     *     graph: array<string, string>,
     *     self_dispositors: list<string>,
     *     final_dispositors: list<string>,
     *     parivartana_yogas: list<array{
     *         planet_a: string,
     *         planet_b: string,
     *         house_a: int,
     *         house_b: int,
     *         type: string,
     *         name: string
     *     }>
     * }
     */
    public static function resolve(array $planetaryRashis, int $lagnaRashi = 0): array
    {
        $graph = [];
        $selfDispositors = [];

        // Build directed edges: planet -> sign lord
        foreach ($planetaryRashis as $planet => $rashi) {
            $lord = ParashariDignities::RASHI_LORDS[$rashi] ?? $planet;
            $graph[$planet] = $lord;

            if ($planet === $lord) {
                $selfDispositors[] = $planet;
            }
        }

        // Detect Parivartana Yogas (Mutual Exchanges)
        $parivartanas = [];
        $planets = array_keys($planetaryRashis);
        $count = count($planets);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $p1 = $planets[$i];
                $p2 = $planets[$j];

                if (($graph[$p1] ?? null) === $p2 && ($graph[$p2] ?? null) === $p1 && $p1 !== $p2) {
                    $house1 = (($planetaryRashis[$p1] - $lagnaRashi + 12) % 12) + 1;
                    $house2 = (($planetaryRashis[$p2] - $lagnaRashi + 12) % 12) + 1;

                    // Classify Parivartana
                    $isDainya = in_array(6, [$house1, $house2], true)
                        || in_array(8, [$house1, $house2], true)
                        || in_array(12, [$house1, $house2], true);

                    $isKhala = (! $isDainya) && (in_array(3, [$house1, $house2], true));

                    $type = match (true) {
                        $isDainya => 'dainya',
                        $isKhala => 'khala',
                        default => 'maha',
                    };

                    $name = match ($type) {
                        'dainya' => 'Dainya Yoga (Difficult Exchange)',
                        'khala' => 'Khala Yoga (Fluctuating Exchange)',
                        default => 'Maha Yoga (Auspicious Exchange)',
                    };

                    $parivartanas[] = [
                        'planet_a' => $p1,
                        'planet_b' => $p2,
                        'house_a' => $house1,
                        'house_b' => $house2,
                        'type' => $type,
                        'name' => $name,
                    ];
                }
            }
        }

        // Trace final dispositors (planets that lead into a self-dispositor)
        $finalDispositors = array_values(array_unique($selfDispositors));

        return [
            'graph' => $graph,
            'self_dispositors' => $selfDispositors,
            'final_dispositors' => $finalDispositors,
            'parivartana_yogas' => $parivartanas,
        ];
    }
}

