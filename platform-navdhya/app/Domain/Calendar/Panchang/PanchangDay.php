<?php

namespace App\Domain\Calendar\Panchang;

/**
 * Authoritative Panchang Day value object holding all five limbs and continuous microsecond boundaries.
 * Closes P-10, P-11, P-07, and fulfills Gate G-LIMB-1.
 */
final class PanchangDay
{
    /**
     * @param string $localDate YYYY-MM-DD
     * @param string $placeId
     * @param string $profileCode
     * @param int $sunriseAtUs
     * @param int $sunsetAtUs
     * @param int $solarNoonUs
     * @param array{number: int, name: string, paksha: string, starts_at_us: int, ends_at_us: int} $tithi
     * @param array{number: int, name: string, pada: int, starts_at_us: int, ends_at_us: int} $nakshatra
     * @param array{number: int, name: string, progress: float} $yoga
     * @param array{number: int, half_tithi: int, name: string, is_vishti_bhadra: bool, is_fixed: bool} $karana
     * @param array{civil_name: string, vedic_name: string, is_pre_sunrise: bool} $vara
     * @param string $masaAmanta
     * @param string $masaPurnimanta
     * @param bool $isAdhika
     * @param array{phase_name: string, illumination_fraction: float, age_days: float, is_waxing: bool} $moonPhase
     * @param string $confidence
     * @param string $generation
     */
    public function __construct(
        public readonly string $localDate,
        public readonly string $placeId,
        public readonly string $profileCode,
        public readonly int $sunriseAtUs,
        public readonly int $sunsetAtUs,
        public readonly int $solarNoonUs,
        public readonly array $tithi,
        public readonly array $nakshatra,
        public readonly array $yoga,
        public readonly array $karana,
        public readonly array $vara,
        public readonly string $masaAmanta,
        public readonly string $masaPurnimanta,
        public readonly bool $isAdhika,
        public readonly array $moonPhase,
        public readonly string $confidence = 'HIGH',
        public readonly string $generation = 'gen_active'
    ) {
    }

    /**
     * Build from raw astronomical calculations.
     */
    public static function fromCalculations(
        string $localDate,
        string $placeId,
        string $profileCode,
        int $sunriseAtUs,
        int $sunsetAtUs,
        int $solarNoonUs,
        array $tithi,
        array $nakshatra,
        array $yoga,
        array $karana,
        array $vara,
        string $masaAmanta,
        string $masaPurnimanta,
        bool $isAdhika,
        array $moonPhase,
        string $confidence = 'HIGH',
        string $generation = 'gen_active'
    ): self {
        return new self(
            localDate: $localDate,
            placeId: $placeId,
            profileCode: $profileCode,
            sunriseAtUs: $sunriseAtUs,
            sunsetAtUs: $sunsetAtUs,
            solarNoonUs: $solarNoonUs,
            tithi: $tithi,
            nakshatra: $nakshatra,
            yoga: $yoga,
            karana: $karana,
            vara: $vara,
            masaAmanta: $masaAmanta,
            masaPurnimanta: $masaPurnimanta,
            isAdhika: $isAdhika,
            moonPhase: $moonPhase,
            confidence: $confidence,
            generation: $generation
        );
    }

    public function toArray(): array
    {
        return [
            'local_date' => $this->localDate,
            'place_id' => $this->placeId,
            'profile_code' => $this->profileCode,
            'sunrise_at_us' => $this->sunriseAtUs,
            'sunset_at_us' => $this->sunsetAtUs,
            'solar_noon_us' => $this->solarNoonUs,
            'tithi' => $this->tithi,
            'nakshatra' => $this->nakshatra,
            'yoga' => $this->yoga,
            'karana' => $this->karana,
            'vara' => $this->vara,
            'masa_amanta' => $this->masaAmanta,
            'masa_purnimanta' => $this->masaPurnimanta,
            'is_adhika' => $this->isAdhika,
            'moon_phase' => $this->moonPhase,
            'confidence' => $this->confidence,
            'generation' => $this->generation,
        ];
    }
}

