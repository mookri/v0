<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Muhurat\Rules;

interface OccasionRuleInterface
{
    public function getOccasionCode(): string;

    public function getOccasionName(): string;

    public function getSourceReference(): string;

    /**
     * @param array<string, mixed> $context (panchang limbs, solar horizon, planetary positions)
     * @return array{score_delta: float, is_disqualified: bool, reasons: list<string>, exclusions: list<string>}
     */
    public function evaluate(array $context): array;
}

