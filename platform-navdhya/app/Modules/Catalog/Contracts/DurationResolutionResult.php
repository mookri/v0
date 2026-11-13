<?php

namespace App\Modules\Catalog\Contracts;

/**
 * Immutable value object representing authoritative duration calculation result (F2).
 */
final class DurationResolutionResult
{
    /**
     * @param array<string, mixed> $evaluatedRules
     */
    public function __construct(
        public readonly int $minutes,
        public readonly int $minBound,
        public readonly int $maxBound,
        public readonly string $ruleSnapshotHash,
        public readonly array $evaluatedRules,
        public readonly bool $isAuthoritative = true
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'minutes' => $this->minutes,
            'min_bound' => $this->minBound,
            'max_bound' => $this->maxBound,
            'rule_snapshot_hash' => $this->ruleSnapshotHash,
            'evaluated_rules' => $this->evaluatedRules,
        ];
    }
}
