<?php

namespace App\Platform\Identity\Events;

/**
 * Platform event `platform.mfa.revoked` (identity catalog): TOTP factor
 * removed for a user.
 */
final class MfaRevoked
{
    public function __construct(public readonly string $userId)
    {
    }

    public function platformEventName(): string
    {
        return 'platform.mfa.revoked';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlatformPayload(): array
    {
        return ['user_id' => $this->userId, 'occurred_at' => now()->toISOString()];
    }
}
