<?php

namespace App\Domain\Jyotish\Astronomy\Time;

/**
 * Value Object representing Julian Day in Terrestrial Time (TT).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class JdTt
{
    public function __construct(
        public float $value,
    ) {
    }

    public function centuriesSinceJ2000(): float
    {
        return ($this->value - 2451545.0) / 36525.0;
    }
}

