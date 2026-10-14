<?php

namespace App\Platform\Identity\Otp;

/**
 * A challenge was issued (fresh row, status `sent`, first send
 * counted). Subsystem lifecycle event — any module may listen for
 * audit/activity hooks without the manager knowing its consumers.
 */
final class OtpChallengeIssued
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $purpose,
        public readonly string $challengeId,
        public readonly string $identifier,
        public readonly string $provider,
    ) {
    }
}
