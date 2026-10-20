<?php

namespace App\Platform\Authz\Events;

use App\Platform\Authz\Models\Policy;

/**
 * Platform event `platform.policy.status_changed` (AC-T-0K01-01-3):
 * emitted with the full policy payload plus the from/to status when a
 * policy transitions draft|active|disabled.
 */
final class PolicyStatusChanged
{
    public function __construct(
        public readonly Policy $policy,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly ?string $actorId = null,
    ) {
    }

    public function platformEventName(): string
    {
        return 'platform.policy.status_changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlatformPayload(): array
    {
        return [
            ...$this->policy->toPayload(),
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'actor_id' => $this->actorId,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
