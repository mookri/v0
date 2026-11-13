<?php

namespace App\Domain\Jyotish\Ephemeris;

/**
 * Port contract for astronomical ephemeris engines.
 * Pure domain contract — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
interface EphemerisProviderInterface
{
    /**
     * Compute an immutable ephemeris snapshot containing 9 graha positions and velocities.
     *
     * @param float $jdTt Julian Day in Terrestrial Time (TT)
     * @param float|null $jdUt1 Optional Julian Day in Universal Time 1 (UT1); falls back to $jdTt if null
     * @return EphemerisSnapshot
     */
    public function computeSnapshot(float $jdTt, ?float $jdUt1 = null): EphemerisSnapshot;
}

