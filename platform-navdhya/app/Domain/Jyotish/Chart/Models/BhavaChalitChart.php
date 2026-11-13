<?php

namespace App\Domain\Jyotish\Chart\Models;

/**
 * Immutable Bhava Chalit Chart Value Object (T-JY03-03).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class BhavaChalitChart
{
    /**
     * @param string $houseSystem
     * @param array<int, array{
     *     house: int,
     *     madhya_degrees: float,
     *     madhya_dms: string,
     *     sandhi_start: float,
     *     sandhi_end: float,
     *     span_degrees: float,
     *     planets: list<string>
     * }> $bhavas
     * @param array<string, array{
     *     planet: string,
     *     sidereal_degrees: float,
     *     dms: string,
     *     rashi_index: int,
     *     rashi_name: string,
     *     d1_bhava: int,
     *     chalit_bhava: int,
     *     shift: string
     * }> $planetaryPlacements
     */
    public function __construct(
        public string $houseSystem,
        public array $bhavas,
        public array $planetaryPlacements,
    ) {
    }

    /**
     * Convert value object to serializable array.
     *
     * @return array{
     *     house_system: string,
     *     bhavas: array<int, array{
     *         house: int,
     *         madhya_degrees: float,
     *         madhya_dms: string,
     *         sandhi_start: float,
     *         sandhi_end: float,
     *         span_degrees: float,
     *         planets: list<string>
     *     }>,
     *     planetary_placements: array<string, array{
     *         planet: string,
     *         sidereal_degrees: float,
     *         dms: string,
     *         rashi_index: int,
     *         rashi_name: string,
     *         d1_bhava: int,
     *         chalit_bhava: int,
     *         shift: string
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'house_system' => $this->houseSystem,
            'bhavas' => $this->bhavas,
            'planetary_placements' => $this->planetaryPlacements,
        ];
    }
}

