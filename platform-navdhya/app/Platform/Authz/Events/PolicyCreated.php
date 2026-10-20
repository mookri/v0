<?php

namespace App\Platform\Authz\Events;

use App\Platform\Authz\Models\Policy;

/**
 * Platform event `platform.policy.created` (AC-T-0K01-01-3): emitted with
 * the full policy payload — including the capability selector — when a
 * policy record is created.
 */
final class PolicyCreated
{
    public function __construct(
        public readonly Policy $policy,
        public readonly ?string $actorId = null,
    ) {
    }

    public function platformEventName(): string
    {
        return 'platform.policy.created';
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
