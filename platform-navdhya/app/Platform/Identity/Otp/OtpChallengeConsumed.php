<?php

namespace App\Platform\Identity\Otp;

/**
 * A challenge was consumed: the row is gone and its single-use
 * redemption is final. Emitted only on real consumption (token match
 * without hold, or a held row consumed after its second factor).
 */
final class OtpChallengeConsumed
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $purpose,
        public readonly string $challengeId,
        public readonly string $identifier,
    ) {
    }
}
