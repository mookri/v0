<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Ashtakavarga\Models;

final readonly class BavMatrix
{
    /**
     * @param string $graha
     * @param array<int, int> $signBindus Array of 12 integers (sign 1..12 => 0..8 bindus)
     * @param array<string, array<int, int>> $prastara 8 contributor rows across 12 signs
     */
    public function __construct(
        public string $graha,
        public array $signBindus,
        public array $prastara = [],
    ) {
    }

    public function getTotalBindus(): int
    {
        return array_sum($this->signBindus);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'graha' => $this->graha,
            'sign_bindus' => $this->signBindus,
            'total_bindus' => $this->getTotalBindus(),
            'prastara' => $this->prastara,
        ];
    }
}

