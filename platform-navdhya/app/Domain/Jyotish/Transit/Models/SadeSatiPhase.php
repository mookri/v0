<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Transit\Models;

final readonly class SadeSatiPhase
{
    public function __construct(
        public string $phase, // RISING (12th), PEAK (1st), SETTING (2nd), DHAIYA_4TH, DHAIYA_8TH, NONE
        public int $saturnRashi,
        public int $natalMoonRashi,
        public int $relativeHouse,
        public bool $isActive,
        public string $description,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'phase' => $this->phase,
            'saturn_rashi' => $this->saturnRashi,
            'natal_moon_rashi' => $this->natalMoonRashi,
            'relative_house' => $this->relativeHouse,
            'is_active' => $this->isActive,
            'description' => $this->description,
        ];
    }
}

