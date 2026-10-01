<?php

namespace App\Platform\Authz\Events;

/**
 * Platform event `platform.sod.conflict_blocked` (SEC-011 §7.4): a role
 * grant was rejected at assignment time because the resulting permission
 * set trips a blocking conflict rule.
 */
final class SodConflictBlocked
{
    /**
     * @param list<string> $blockingRules
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $membershipId,
        public readonly string $roleCode,
        public readonly array $blockingRules,
        public readonly ?string $actorId = null,
    ) {
    }

    public function platformEventName(): string
    {
        return 'platform.sod.conflict_blocked';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlatformPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'membership_id' => $this->membershipId,
            'role_code' => $this->roleCode,
            'blocking_rules' => $this->blockingRules,
            'actor_id' => $this->actorId,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
