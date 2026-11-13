<?php

namespace App\Modules\Customers\Providers;

/**
 * Result of a notification dispatch attempt (E4).
 */
final class ProviderSendResult
{
    /**
     * @param array<string, mixed>|null $rawResponse
     */
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $error = null,
        public readonly ?array $rawResponse = null,
        public readonly bool $isSimulated = false,
        public readonly bool $isAmbiguous = false,
    ) {
    }

    /**
     * @param array<string, mixed>|null $raw
     */
    public static function success(string $messageId, ?array $raw = null, bool $isSimulated = false): self
    {
        return new self(
            ok: true,
            providerMessageId: $messageId,
            rawResponse: $raw,
            isSimulated: $isSimulated
        );
    }

    /**
     * @param array<string, mixed>|null $raw
     */
    public static function failure(string $error, ?array $raw = null, bool $isAmbiguous = false): self
    {
        return new self(
            ok: false,
            error: $error,
            rawResponse: $raw,
            isAmbiguous: $isAmbiguous
        );
    }

    /**
     * @param array<string, mixed>|null $raw
     */
    public static function ambiguous(string $error, ?array $raw = null): self
    {
        return new self(
            ok: false,
            error: $error,
            rawResponse: $raw,
            isAmbiguous: true
        );
    }
}
