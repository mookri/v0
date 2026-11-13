<?php

namespace App\Domain\Jyotish\Profiles;

use InvalidArgumentException;

/**
 * Value object encapsulating Jyotish calculation profile conventions.
 * Strictly immutable, pure domain object.
 */
final readonly class JyotishProfile
{
    public const DEFAULT_CODE = 'parashari-lahiri-sripati';

    public function __construct(
        public string $profile_code = self::DEFAULT_CODE,
        public string $ephemeris_model = 'meeus',
        public string $ephemeris_version = 'v1',
        public string $ayanamsa_code = 'lahiri',
        public string $ayanamsa_version = 'v1',
        public string $house_system = 'sripati',
        public string $node_mode = 'mean',
        public string $masa_system = 'PURNIMANTA',
        public string $day_anchor = 'SUNRISE',
        public int $rules_version = 2
    ) {
        if (! in_array($this->ayanamsa_code, ['lahiri', 'raman', 'kp', 'fagan_bradley'], true)) {
            throw new InvalidArgumentException("Unsupported ayanamsa: {$this->ayanamsa_code}");
        }

        if (! in_array($this->house_system, ['whole_sign', 'sripati', 'placidus'], true)) {
            throw new InvalidArgumentException("Unsupported house system: {$this->house_system}");
        }

        if (! in_array($this->node_mode, ['mean', 'true'], true)) {
            throw new InvalidArgumentException("Unsupported node mode: {$this->node_mode}");
        }

        if (! in_array($this->masa_system, ['AMANTA', 'PURNIMANTA'], true)) {
            throw new InvalidArgumentException("Unsupported masa system: {$this->masa_system}");
        }

        if (! in_array($this->day_anchor, ['SUNRISE', 'MIDNIGHT'], true)) {
            throw new InvalidArgumentException("Unsupported day anchor: {$this->day_anchor}");
        }
    }

    public static function defaultProfile(): self
    {
        return new self();
    }

    /**
     * Layered cache key generation (ADR-JY-04 / JY-OPT-02).
     */
    public function cacheKey(string $prefix = 'jy'): string
    {
        return sprintf(
            '%s:%s:%s_%s:%s_%s:%s:%s:v%d',
            $prefix,
            $this->profile_code,
            $this->ephemeris_model,
            $this->ephemeris_version,
            $this->ayanamsa_code,
            $this->ayanamsa_version,
            $this->house_system,
            $this->node_mode,
            $this->rules_version
        );
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
            'house_system' => $this->house_system,
            'node_mode' => $this->node_mode,
            'masa_system' => $this->masa_system,
            'day_anchor' => $this->day_anchor,
            'rules_version' => $this->rules_version,
        ];
    }
}

