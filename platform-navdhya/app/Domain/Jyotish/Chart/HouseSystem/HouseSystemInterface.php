<?php

namespace App\Domain\Jyotish\Chart\HouseSystem;

/**
 * Contract for Astronomical and Jyotish House Systems (T-JY03-02).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
interface HouseSystemInterface
{
    /**
     * Compute 12 house cusps, boundaries (sandhis), and rashi placements.
     *
     * @param float $ascendantDegrees Sidereal Ascendant (Lagna) in degrees [0, 360)
     * @param float $mcDegrees Sidereal Midheaven (MC) in degrees [0, 360)
     * @param float $latitude Geographic latitude in degrees [-90, +90]
     * @param float $ramcDegrees Local Sidereal Time (RAMC) in degrees [0, 360)
     * @param float $obliquityDegrees True obliquity of the ecliptic in degrees
     * @return array<int, array{
     *     house: int,
     *     cusp_degrees: float,
     *     dms: string,
     *     rashi_index: int,
     *     rashi_name: string,
     *     degrees_in_rashi: float,
     *     sandhi_start: float,
     *     sandhi_end: float,
     *     span_degrees: float
     * }>
     */
    public function calculateCusps(
        float $ascendantDegrees,
        float $mcDegrees,
        float $latitude = 0.0,
        float $ramcDegrees = 0.0,
        float $obliquityDegrees = 23.44
    ): array;

    public function systemCode(): string;

    public function systemName(): string;
}

