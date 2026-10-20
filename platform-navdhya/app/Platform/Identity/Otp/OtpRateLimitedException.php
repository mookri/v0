<?php

namespace App\Platform\Identity\Otp;

/**
 * The delivery policy refused this action: too many resends on the
 * live challenge, a resend inside its compounding back-off window, or
 * too many fresh challenges in the rolling hour. `retryAfterSeconds`
 * (when known) feeds the HTTP `Retry-After` header; controllers keep
 * branching on the exception type, never on the number.
 */
final class OtpRateLimitedException extends \RuntimeException
{
    public function __construct(public readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct('otp rate limited');
    }
}
