<?php

namespace App\Domain\Jyotish\Ephemeris;

use App\Domain\Jyotish\Contracts\ComputesEphemeris;
use InvalidArgumentException;

/**
 * Immutable snapshot holding tropical coordinates and velocity vectors
 * for all 9 classical grahas at a single instant in time.
 * Pure domain model — zero HTTP or framework dependencies.
 */
final readonly class EphemerisSnapshot implements ComputesEphemeris
{
    public const GRAHAS = [
        'sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn', 'rahu', 'ketu'
    ];

    /**
     * @param float $jdTt Julian Day in Terrestrial Time
     * @param float $jdUt1 Julian Day in Universal Time 1
     * @param array<string, array{longitude: float, latitude: float, distance: float, speed_longitude: float, speed_latitude: float, speed_distance: float}> $grahas
     */
    public function __construct(
        public float $jdTt,
        public float $jdUt1,
        public array $grahas
    ) {
        foreach (self::GRAHAS as $graha) {
            if (! isset($this->grahas[$graha])) {
                throw new InvalidArgumentException("Missing required graha in snapshot: {$graha}");
            }
        }
    }

    public function getJdTt(): float
    {
        return $this->jdTt;
    }

    public function getJdUt1(): float
    {
        return $this->jdUt1;
    }

    /**
     * @return array<string, array{longitude: float, latitude: float, distance: float, speed_longitude: float, speed_latitude: float, speed_distance: float}>
     */
    public function getGrahaPositions(): array
    {
        return $this->grahas;
    }

    /**
     * @param string $graha
     * @return array{longitude: float, latitude: float, distance: float, speed_longitude: float, speed_latitude: float, speed_distance: float}
     */
    public function getPlanet(string $graha): array
    {
        $key = strtolower($graha);
        if (! isset($this->grahas[$key])) {
            throw new InvalidArgumentException("Unknown graha: {$graha}");
        }

        return $this->grahas[$key];
    }
}

