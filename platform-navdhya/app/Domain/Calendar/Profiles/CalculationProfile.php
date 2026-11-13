<?php

namespace App\Domain\Calendar\Profiles;

use InvalidArgumentException;

/**
 * Value object encapsulating astronomical calculation and profile conventions.
 */
final class CalculationProfile
{
    public const DEFAULT_CODE = 'drik-lahiri-purnimanta';

    public function __construct(
        public readonly string $profile_code,
        public readonly string $ephemeris_model = 'meeus',
        public readonly string $ephemeris_version = 'v1',
        public readonly string $ayanamsa_code = 'lahiri',
        public readonly string $ayanamsa_version = 'v1',
        public readonly string $masa_system = 'PURNIMANTA',
        public readonly string $sunrise_convention = 'civil_atmospheric_refraction',
        public readonly string $day_anchor = 'SUNRISE',
        public readonly int $rules_version = 2
    ) {
        if (! in_array($this->masa_system, ['AMANTA', 'PURNIMANTA'], true)) {
            throw new InvalidArgumentException("Invalid masa system: {$this->masa_system}");
        }

        if (! in_array($this->day_anchor, ['SUNRISE', 'MIDNIGHT'], true)) {
            throw new InvalidArgumentException("Invalid day anchor: {$this->day_anchor}");
        }
    }

    public static function defaultProfile(): self
    {
        return new self(
            profile_code: self::DEFAULT_CODE,
            ephemeris_model: 'meeus',
            ephemeris_version: 'v1',
            ayanamsa_code: 'lahiri',
            ayanamsa_version: 'v1',
            masa_system: 'PURNIMANTA',
            sunrise_convention: 'civil_atmospheric_refraction',
            day_anchor: 'SUNRISE',
            rules_version: 2
        );
    }

    public static function amantaProfile(): self
    {
        return new self(
            profile_code: 'drik-lahiri-amanta',
            ephemeris_model: 'meeus',
            ephemeris_version: 'v1',
            ayanamsa_code: 'lahiri',
            ayanamsa_version: 'v1',
            masa_system: 'AMANTA',
            sunrise_convention: 'civil_atmospheric_refraction',
            day_anchor: 'SUNRISE',
            rules_version: 2
        );
    }

    public static function fromCode(string $code): self
    {
        return match (strtolower(trim($code))) {
            'drik-lahiri-amanta' => self::amantaProfile(),
            'drik-lahiri-purnimanta', 'default' => self::defaultProfile(),
            default => new self(profile_code: $code),
        };
    }

    public function cacheKey(): string
    {
        return "{$this->profile_code}:{$this->ephemeris_version}:{$this->ayanamsa_version}:r{$this->rules_version}";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'profile_code' => $this->profile_code,
            'ephemeris_model' => $this->ephemeris_model,
            'ephemeris_version' => $this->ephemeris_version,
            'ayanamsa_code' => $this->ayanamsa_code,
            'ayanamsa_version' => $this->ayanamsa_version,
            'masa_system' => $this->masa_system,
            'sunrise_convention' => $this->sunrise_convention,
            'day_anchor' => $this->day_anchor,
            'rules_version' => $this->rules_version,
        ];
    }
}

