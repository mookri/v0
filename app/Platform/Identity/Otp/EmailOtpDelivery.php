<?php

namespace App\Platform\Identity\Otp;

/**
 * One email OTP delivery job (immutable value object).
 */
final readonly class EmailOtpDelivery
{
    public function __construct(
        public string $to,
        public string $code,
        public string $challengeId,
        public string $purpose,
        public string $expiresAt,
    ) {
    }
}
