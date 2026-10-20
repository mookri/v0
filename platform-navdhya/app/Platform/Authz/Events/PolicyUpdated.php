<?php

namespace App\Platform\Authz\Events;

use App\Platform\Authz\Models\Policy;

/**
 * Platform event `platform.policy.updated` (AC-T-0K01-01-3): emitted with
 * the full policy payload when a policy's content changes.
 */
final class PolicyUpdated
{
    public function __construct(
        public readonly Policy $policy,
        public readonly ?string $actorId = null,
    ) {
    }

    public function platformEventName(): string
    {
        return 'platform.policy.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlatformPayload(): array
    {
        return [
            ...$this->policy->toPayload(),
            'actor_id' => $this->actorId,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
