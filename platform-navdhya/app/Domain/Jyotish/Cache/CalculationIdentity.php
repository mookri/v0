<?php

namespace App\Domain\Jyotish\Cache;

/**
 * Immutable calculation identity value object (T-JYOPT-01 / JY-OPT-01).
 *
 * Encapsulates the complete set of parameters that uniquely determine
 * a Jyotish astronomical calculation.
 */
final readonly class CalculationIdentity
{
    public function __construct(
        public float $julianDay,
        public float $latitude,
        public float $longitude,
        public float $altitudeM = 0.0,
        public string $ayanamsa = 'lahiri',
        public string $houseSystem = 'whole_sign',
        public string $ephemerisModel = 'meeus_truncated',
        public string $rulesVersion = 'v2'
    ) {}

    /**
     * Canonical normalized array for deterministic hashing.
     * Coordinate floats rounded to 1e-6 degrees (~11cm precision).
     */
    public function toNormalizedArray(): array
    {
        return [
            'altitude_m' => round($this->altitudeM, 1),
            'ayanamsa' => strtolower($this->ayanamsa),
            'ephemeris_model' => strtolower($this->ephemerisModel),
            'house_system' => strtolower($this->houseSystem),
            'julian_day' => round($this->julianDay, 7),
            'latitude' => round($this->latitude, 6),
            'longitude' => round($this->longitude, 6),
            'rules_version' => strtolower($this->rulesVersion),
        ];
    }
}

