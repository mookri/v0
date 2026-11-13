<?php

namespace App\Domain\Jyotish\Contracts;

/**
 * Contract for ephemeris snapshots and providers computing celestial coordinates.
 * Pure domain contract — zero HTTP or framework dependencies.
 */
interface ComputesEphemeris
{
    /**
     * Get Julian Day in Terrestrial Time (TT).
     */
    public function getJdTt(): float;

    /**
     * Get Julian Day in Universal Time (UT1).
     */
    public function getJdUt1(): float;

    /**
     * Get tropical celestial positions for all 9 grahas at the snapshot instant.
     *
     * @return array<string, array{longitude: float, latitude: float, distance: float, speed_longitude: float, speed_latitude: float, speed_distance: float}>
     */
    public function getGrahaPositions(): array;
}

