<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Transit\Models;

final readonly class TransitAspect
{
    public function __construct(
        public string $graha,
        public int $natalHouse,
        public string $nature, // benefic, malefic, neutral
        public string $description,
        public \DateTimeImmutable $ingressAt,
        public ?\DateTimeImmutable $egressAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'graha' => $this->graha,
            'natal_house' => $this->natalHouse,
            'nature' => $this->nature,
            'description' => $this->description,
            'ingress_at' => $this->ingressAt->format(\DateTimeInterface::ATOM),
            'egress_at' => $this->egressAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}

