<?php

namespace App\Domain\Jyotish\Contracts;

use App\Domain\Jyotish\Profiles\JyotishProfile;

/**
 * Contract for engines producing astrological charts (D1, divisional vargas, chalit).
 * Pure domain contract — zero HTTP or framework dependencies.
 */
interface ComputesChart
{
    /**
     * Compute a chart for a given astronomical snapshot and calculation profile.
     *
     * @param ComputesEphemeris $ephemeris
     * @param JyotishProfile $profile
     * @return array<string, mixed>
     */
    public function compute(ComputesEphemeris $ephemeris, JyotishProfile $profile): array;
}

