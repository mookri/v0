<?php

namespace App\Platform\Identity\Otp;

/**
 * Provider verdict on one access token. `verified` + `identifier`
 * (the identifier the provider attests, provider dialect) on success;
 * `verified` false + a symbolic `reason` on rejection — the reason is
 * safe for logs (it names the token's failure, never the phone) but
 * never reaches the client, where it would collapse into the uniform
 * 401 anyway.
 */
final readonly class OtpTokenResult
{
    public function __construct(
        public bool $verified,
        public ?string $identifier = null,
        public ?string $reason = null,
    ) {
    }

    public static function attested(string $identifier): self
    {
        return new self(verified: true, identifier: $identifier);
    }

    public static function rejected(?string $reason = null): self
    {
        return new self(verified: false, reason: $reason);
    }
}
