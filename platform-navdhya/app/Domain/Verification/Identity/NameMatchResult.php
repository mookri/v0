<?php

namespace App\Domain\Verification\Identity;

final class NameMatchResult
{
    public function __construct(
        public readonly float $score,
        public readonly NameMatchBand $band,
        public readonly string $normalizedLeft,
        public readonly string $normalizedRight,
        public readonly bool $advisoryOnly = true,
    ) {
    }
}

