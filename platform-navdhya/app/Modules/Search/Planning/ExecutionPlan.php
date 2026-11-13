<?php

namespace App\Modules\Search\Planning;

class ExecutionPlan
{
    /**
     * @param list<string> $stages
     * @param array<string, mixed> $features
     * @param array<string, int> $budgets
     */
    public function __construct(
        public readonly string $queryRaw,
        public readonly string $queryNorm,
        public readonly array $stages,
        public readonly array $features,
        public readonly array $budgets,
        public bool $truncated = false,
        public ?string $earlyExitReason = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'query_raw' => $this->queryRaw,
            'query_norm' => $this->queryNorm,
            'stages' => $this->stages,
            'features' => $this->features,
            'budgets' => $this->budgets,
            'truncated' => $this->truncated,
            'early_exit_reason' => $this->earlyExitReason,
        ];
    }
}
