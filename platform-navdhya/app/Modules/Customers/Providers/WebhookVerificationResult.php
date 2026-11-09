<?php

namespace App\Modules\Customers\Providers;

/**
 * Result of webhook HMAC verification and status parsing (E4).
 */
final class WebhookVerificationResult
{
    /**
     * @param array<string, mixed>|null $payload
     */
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $providerMessageId = null,
        public readonly string $status = 'pending', // sent | delivered | read | failed
        public readonly ?string $error = null,
        public readonly ?array $payload = null,
    ) {
    }

    public static function invalid(string $reason): self
    {
        return new self(valid: false, error: $reason);
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    public static function matched(string $messageId, string $status, ?array $payload = null): self
    {
        return new self(valid: true, providerMessageId: $messageId, status: $status, payload: $payload);
    }
}
