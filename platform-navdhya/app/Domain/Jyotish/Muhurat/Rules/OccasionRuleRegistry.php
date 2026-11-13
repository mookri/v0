<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Muhurat\Rules;

use App\Domain\Jyotish\Muhurat\Rules\Occasions\BusinessOpeningRule;
use App\Domain\Jyotish\Muhurat\Rules\Occasions\GrihaPraveshRule;
use App\Domain\Jyotish\Muhurat\Rules\Occasions\VivahRule;

final class OccasionRuleRegistry
{
    /** @var array<string, OccasionRuleInterface> */
    private array $rules = [];

    public function __construct()
    {
        $this->register(new VivahRule());
        $this->register(new GrihaPraveshRule());
        $this->register(new BusinessOpeningRule());
    }

    public function register(OccasionRuleInterface $rule): void
    {
        $this->rules[$rule->getOccasionCode()] = $rule;
    }

    public function get(string $occasionCode): ?OccasionRuleInterface
    {
        return $this->rules[strtoupper($occasionCode)] ?? null;
    }

    /**
     * @return list<OccasionRuleInterface>
     */
    public function all(): array
    {
        return array_values($this->rules);
    }
}

