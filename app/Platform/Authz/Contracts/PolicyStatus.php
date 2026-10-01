<?php

namespace App\Platform\Authz\Contracts;

/**
 * Policy lifecycle status. Transitions: draft|disabled -> active
 * (activate), active|draft -> disabled (disable). Anything else is a
 * 409 `state_forbidden` state transition error.
 */
enum PolicyStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Disabled = 'disabled';

    /**
     * @return list<PolicyStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Active, self::Disabled],
            self::Active => [self::Disabled],
            self::Disabled => [self::Active],
        };
    }

    public function canTransitionTo(PolicyStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
