<?php

namespace App\Platform\Identity\Otp;

/**
 * OTP risk tiers (Verification Fabric S48, decided up front so every
 * purpose carries its tier from day one rather than retrofitting):
 *
 *  - T0: possession re-proof of an existing contact (login, verify_*);
 *  - T1: account creation on a new identifier (signup);
 *  - T2: contact swaps (change_phone / change_email) — 2-of-2 dual-
 *        channel quorum, enforced from S50;
 *  - T3: vendor onboarding-kyc — 2-of-2 dual-channel quorum (S50,
 *        joins with the vendor audience).
 *
 * Only the quorum requirement is tier-driven today; the challenge
 * state machine (caps, supersession, TTL, attempts) stays identical
 * for every tier until quorum lands.
 */
enum OtpRiskTier: string
{
    case T0 = 'T0';

    case T1 = 'T1';

    case T2 = 'T2';

    case T3 = 'T3';

    /**
     * Channels that must independently confirm before the step
     * completes (1-of-1 single channel, 2-of-2 dual channel).
     */
    public function quorumChannels(): int
    {
        return match ($this) {
            self::T0, self::T1 => 1,
            self::T2, self::T3 => 2,
        };
    }
}
