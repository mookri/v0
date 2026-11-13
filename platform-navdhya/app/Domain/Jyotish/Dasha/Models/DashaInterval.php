<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Dasha\Models;

final readonly class DashaInterval
{
    /**
     * @param list<DashaInterval> $subPeriods
     */
    public function __construct(
        public string $system,
        public int $level,
        public string $lord,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public float $durationYears,
        public array $subPeriods = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'system' => $this->system,
            'level' => $this->level,
            'lord' => $this->lord,
            'starts_at' => $this->startsAt->format(\DateTimeInterface::ATOM),
            'ends_at' => $this->endsAt->format(\DateTimeInterface::ATOM),
            'duration_years' => round($this->durationYears, 4),
            'sub_periods' => array_map(static fn (DashaInterval $sub) => $sub->toArray(), $this->subPeriods),
        ];
    }
}

