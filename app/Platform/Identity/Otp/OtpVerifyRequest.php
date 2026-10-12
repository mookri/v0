<?php

namespace App\Platform\Identity\Otp;

/**
 * One redemption attempt as seen by a provider. `codeHash` is the local
 * comparison target (local providers), `providerRef` the remote handle
 * (remote providers encode the phone in the session — no identifier
 * needed to verify).
 */
final readonly class OtpVerifyRequest
{
    public function __construct(
        public ?string $codeHash,
        public ?string $providerRef,
        public string $code,
    ) {
    }
}
