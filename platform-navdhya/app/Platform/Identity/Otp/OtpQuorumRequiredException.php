<?php

namespace App\Platform\Identity\Otp;

use RuntimeException;

final class OtpQuorumRequiredException extends RuntimeException
{
    public function __construct(string $purpose, int $requiredChannels)
    {
        parent::__construct(sprintf(
            'OTP purpose [%s] requires a %d-of-%d multi-channel quorum via verifyQuorum().',
            $purpose,
            $requiredChannels,
            $requiredChannels,
        ));
    }
}
