<?php

namespace App\Platform\Identity\OAuth;

/**
 * Platform event `platform.token.reuse_detected` (SEC-015 catalog): a
 * revoked refresh token was presented again, so the whole lineage was
 * killed. Security-alert surface (SEC-003 §3.2).
 */
final class TokenReuseDetected
{
    public function __construct(
        public readonly string $userId,
        public readonly string $clientId,
    ) {
    }

    public function platformEventName(): string
    {
        return 'platform.token.reuse_detected';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlatformPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'client_id' => $this->clientId,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
