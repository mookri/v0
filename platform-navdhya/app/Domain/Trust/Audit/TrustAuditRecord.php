<?php

declare(strict_types=1);

namespace App\Domain\Trust\Audit;

use Carbon\CarbonImmutable;

/**
 * Canonical transition audit payload matching spec §16 (support_case_status_history
 * and incident_timeline shapes).
 */
final readonly class TrustAuditRecord
{
    public function __construct(
        public string $actorId,
        public string $actorType,
        public string $action,
        public string $previousState,
        public string $newState,
        public string $reason,
        public string $policyVersion,
        public string $correlationId,
        public CarbonImmutable $occurredAt,
    ) {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Audit reason cannot be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'actor_id' => $this->actorId,
            'actor_type' => $this->actorType,
            'action' => $this->action,
            'previous_state' => $this->previousState,
            'new_state' => $this->newState,
            'reason' => $this->reason,
            'policy_version' => $this->policyVersion,
            'correlation_id' => $this->correlationId,
            'occurred_at' => $this->occurredAt->toJSON(),
        ];
    }
}

