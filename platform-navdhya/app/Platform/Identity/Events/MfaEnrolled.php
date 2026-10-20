<?php

namespace App\Platform\Identity\Events;

/**
 * Platform event `platform.mfa.enrolled` (identity catalog): TOTP
 * enrollment confirmed for a user.
 */
final class MfaEnrolled
{
    public function __construct(public readonly string $userId)
    {
    }

    public function platformEventName(): string
    {
        return 'platform.mfa.enrolled';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlatformPayload(): array
    {
        return ['user_id' => $this->userId, 'occurred_at' => now()->toISOString()];
    }
}
