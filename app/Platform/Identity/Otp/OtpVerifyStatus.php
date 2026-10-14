<?php

namespace App\Platform\Identity\Otp;

/**
 * Normalized verify verdict from OtpChallengeManager::verify —
 * NotFound and Mismatch collapse into the caller's uniform 401 (no
 * oracle), Held pauses the row at `token_verified` for a second
 * factor (MFA step-up), Match is a consumed challenge. Provider
 * outages do not appear here: they throw the typed dependency
 * exception instead.
 */
enum OtpVerifyStatus
{
    case NotFound;

    case Mismatch;

    case Held;

    case Match;
}
