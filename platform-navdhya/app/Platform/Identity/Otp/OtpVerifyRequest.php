<?php

namespace App\Platform\Identity\Otp;

/**
 * One redemption attempt as seen by a provider. `codeHash` is the
 * local comparison target (code-holding providers), `providerRef`
 * the remote handle, and `identifier` the challenge row's own
 * identifier — token-attesting providers compare what the provider
 * attests against it (canonicalized) without ever storing or
 * rewriting the row.
 */
final readonly class OtpVerifyRequest
{
    public function __construct(
        public ?string $codeHash,
        public ?string $providerRef,
        public string $code,
        public string $identifier,
    ) {
    }
}
