<?php

namespace App\Platform\Identity;

use RuntimeException;

/**
 * The step-up challenge cap (5 per hour per identifier) is exhausted.
 *
 * Typed rather than a bare RuntimeException so the surface can answer the
 * honest 429 "wait and try again" instead of a 500 for what is an expected,
 * rate-limited outcome — the same shape MfaLockedException gives MFA
 * lockouts.
 */
final class StepUpRateLimitedException extends RuntimeException
{
}
