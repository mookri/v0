<?php

namespace App\Platform\Identity\Otp;

/**
 * A redemption attempt failed (wrong code / wrong identifier / used
 * token): the row kept its attempt bump. Not emitted for expired or
 * exhausted challenges — those are policy outcomes, not attempts.
 */
final class OtpChallengeRejected
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $purpose,
        public readonly string $challengeId,
        public readonly int $attempts,
    ) {
    }
}
