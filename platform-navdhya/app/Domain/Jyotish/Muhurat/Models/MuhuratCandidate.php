<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Muhurat\Models;

final readonly class MuhuratCandidate
{
    /**
     * @param list<string> $reasons
     * @param list<string> $exclusions
     * @param array<string, mixed> $planetarySnapshot
     */
    public function __construct(
        public string $candidateId,
        public string $occasionCode,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public float $score,
        public bool $isDisqualified,
        public array $reasons,
        public array $exclusions,
        public array $planetarySnapshot,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'candidate_id' => $this->candidateId,
            'occasion_code' => $this->occasionCode,
            'starts_at' => $this->startsAt->format(\DateTimeInterface::ATOM),
            'ends_at' => $this->endsAt->format(\DateTimeInterface::ATOM),
            'duration_minutes' => (int) (($this->endsAt->getTimestamp() - $this->startsAt->getTimestamp()) / 60),
            'score' => round($this->score, 2),
            'is_disqualified' => $this->isDisqualified,
            'reasons' => $this->reasons,
            'exclusions' => $this->exclusions,
            'planetary_snapshot' => $this->planetarySnapshot,
        ];
    }
}

