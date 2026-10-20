<?php

namespace App\Platform\Identity\Otp;

/**
 * A resend was granted (schedule validated server-side, row bumped);
 * the client still has to actually trigger the provider retry.
 */
final class OtpChallengeResent
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $purpose,
        public readonly string $challengeId,
        public readonly string $identifier,
        public readonly string $channel,
        public readonly int $resendCount,
    ) {
    }
}
