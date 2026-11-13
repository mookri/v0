<?php

namespace App\Domain\Jyotish\Chart\Varga;

/**
 * Shodasha Varga Multi-Chart Calculator (T-JY04-07).
 *
 * Computes all 16 divisional charts for Lagna and 9 grahas:
 * D1, D2, D3, D4, D7, D9, D10, D12, D16, D20, D24, D27, D30, D40, D45, D60.
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class VargaCalculator
{
    /**
     * Compute full 16 Shodasha Varga placements for Lagna and all planets.
     *
     * @param float $lagnaDegrees Sidereal Ascendant degree
     * @param array<string, float> $planetaryDegrees Map of planet => sidereal longitude
     * @return array<string, array{
     *     varga: string,
     *     name: string,
     *     division: int,
     *     domain: string,
     *     lagna: array{rashi_index: int, rashi_name: string, rashi_lord: string, degrees_in_varga: float},
     *     planets: array<string, array{rashi_index: int, rashi_name: string, rashi_lord: string, degrees_in_varga: float}>
     * }>
     */
    public static function calculateAll(float $lagnaDegrees, array $planetaryDegrees): array
    {
        $vargas = [];

        foreach (VargaHarmonicEngine::SUPPORTED_VARGAS as $code => $meta) {
            $lagnaVarga = VargaHarmonicEngine::calculate($code, $lagnaDegrees);

            $planetPlacements = [];
            foreach ($planetaryDegrees as $planet => $deg) {
                $planetPlacements[$planet] = VargaHarmonicEngine::calculate($code, $deg);
            }

            $vargas[$code] = [
                'varga' => $code,
                'name' => $meta['name'],
                'division' => $meta['division'],
                'domain' => $meta['domain'],
                'lagna' => $lagnaVarga,
                'planets' => $planetPlacements,
            ];
        }

        return $vargas;
    }
}

