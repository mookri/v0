<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Immutable Panchang astronomical occurrence result (pure domain).
 */
final class PanchangOccurrence
{
    /**
     * @param array{number: int, name: string, paksha: string, index_in_paksha: int, progress: float} $tithi
     * @param array{number: int, name: string, pada: int, progress: float} $nakshatra
     * @param array{amanta: string, purnimanta: string, is_adhika: bool, solar_rashi_index: int} $masa
     */
    public function __construct(
        public readonly string $date, // YYYY-MM-DD
        public readonly string $sunriseUtc,
        public readonly array $tithi,
        public readonly array $nakshatra,
        public readonly array $masa,
        public readonly string $tradition, // AMANTA | PURNIMANTA
        public readonly string $confidence, // HIGH | MEDIUM | LOW
    ) {
    }

    public function resolvedMasa(): string
    {
        return $this->tradition === 'AMANTA'
            ? $this->masa['amanta']
            : $this->masa['purnimanta'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'sunrise_utc' => $this->sunriseUtc,
            'tithi' => $this->tithi,
            'nakshatra' => $this->nakshatra,
            'masa' => $this->masa,
            'resolved_masa' => $this->resolvedMasa(),
            'tradition' => $this->tradition,
            'confidence' => $this->confidence,
        ];
    }
}
