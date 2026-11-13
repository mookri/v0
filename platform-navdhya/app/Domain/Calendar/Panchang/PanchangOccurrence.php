<?php

namespace App\Domain\Calendar\Panchang;

/**
 * Value object representing astronomical Panchang calculation for a specific civil date.
 */
final class PanchangOccurrence
{
    /**
     * @param string $date ISO Date YYYY-MM-DD
     * @param string $sunriseUtc ISO UTC Sunrise Instant
     * @param array{number: int, name: string, paksha: string, index_in_paksha: int, progress: float} $tithi
     * @param array{number: int, name: string, pada: int, progress: float} $nakshatra
     * @param array{amanta: string, purnimanta: string, is_adhika: bool, solar_rashi_index: int} $masa
     * @param array{number: int, name: string, progress: float}|null $yoga
     * @param array{number: int, half_tithi: int, name: string, is_vishti_bhadra: bool, is_fixed: bool, progress: float}|null $karana
     * @param array{civil_number: int, civil_name: string, vedic_number: int, vedic_name: string, is_pre_sunrise: bool}|null $vara
     * @param string $tradition AMANTA | PURNIMANTA
     * @param string $confidence HIGH | MEDIUM | LOW
     */
    public function __construct(
        public readonly string $date,
        public readonly string $sunriseUtc,
        public readonly array $tithi,
        public readonly array $nakshatra,
        public readonly array $masa,
        public readonly ?array $yoga = null,
        public readonly ?array $karana = null,
        public readonly ?array $vara = null,
        public readonly string $tradition = 'PURNIMANTA',
        public readonly string $confidence = 'HIGH'
    ) {
    }

    /**
     * Get resolved Lunar Month name based on requested tradition.
     */
    public function resolvedMasa(): string
    {
        return $this->tradition === 'AMANTA'
            ? $this->masa['amanta']
            : $this->masa['purnimanta'];
    }
}
