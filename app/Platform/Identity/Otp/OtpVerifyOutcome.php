<?php

namespace App\Platform\Identity\Otp;

/**
 * Normalized verify outcome — every provider error taxonomy collapses
 * into these three before it can reach a controller:
 * - Match: redeem and consume the challenge;
 * - Mismatch: the ordinary attempt bump + uniform 401;
 * - Unavailable: fail closed (typed dependency error), never a 401 and
 *   never a silent provider downgrade.
 */
enum OtpVerifyOutcome
{
    case Match;

    case Mismatch;

    case Unavailable;
}
