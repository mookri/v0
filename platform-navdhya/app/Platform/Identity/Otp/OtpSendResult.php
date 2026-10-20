<?php

namespace App\Platform\Identity\Otp;

/**
 * Result of a provider send. Exactly one side is ever populated: local
 * providers hand back the plaintext code (service hashes it into the
 * row), remote providers hand back an opaque provider_ref (row stores
 * it; the code never exists on our side). A send producing neither is a
 * misconfigured provider — the service fails closed.
 */
final readonly class OtpSendResult
{
    public function __construct(
        public ?string $code = null,
        public ?string $providerRef = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->code === null && $this->providerRef === null;
    }
}
