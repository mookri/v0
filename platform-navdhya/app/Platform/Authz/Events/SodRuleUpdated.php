<?php

namespace App\Platform\Authz\Events;

use App\Platform\Authz\Models\SodRule;

/**
 * Platform event `platform.sod.rule_updated` (SEC-011 §7.4): a tenant
 * SoD rule changed (severity/status/sets).
 */
final class SodRuleUpdated
{
    public function __construct(
        public readonly SodRule $rule,
        public readonly ?string $actorId = null,
    ) {
    }

    public function platformEventName(): string
    {
        return 'platform.sod.rule_updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlatformPayload(): array
    {
        return [
            'id' => (string) $this->rule->getKey(),
            'tenant_id' => $this->rule->tenant_id,
            'code' => $this->rule->code,
            'kind' => $this->rule->kind,
            'severity' => $this->rule->severity,
            'status' => $this->rule->status,
            'actor_id' => $this->actorId,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
