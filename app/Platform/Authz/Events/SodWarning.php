<?php

namespace App\Platform\Authz\Events;

/**
 * Platform event `platform.sod.warning` (SEC-011 §7.4): a role grant
 * proceeded despite tripping a warn-severity conflict rule. The grant is
 * valid; this is the security-alert surface.
 */
final class SodWarning
{
    /**
     * @param list<string> $warningRules
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $membershipId,
        public readonly string $roleCode,
        public readonly array $warningRules,
        public readonly ?string $actorId = null,
    ) {
    }

    public function platformEventName(): string
    {
        return 'platform.sod.warning';
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
            'warning_rules' => $this->warningRules,
            'actor_id' => $this->actorId,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
