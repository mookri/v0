<?php

namespace App\Platform\Identity\Otp;

/**
 * The provider itself refused for rate-limit reasons (Google's
 * `TOO_MANY_ATTEMPTS_TRY_LATER` family) — a retryable condition on the
 * identifier, distinct from a dependency fault.
 *
 * Extends the unavailable marker on purpose: every caller that already
 * fails closed on a provider fault keeps failing closed here, and only
 * the HTTP shape differs (429 instead of 503). Nothing may retry in a
 * loop — the back-off window re-arms on every attempt.
 */
class OtpProviderRateLimitedException extends OtpProviderUnavailableException
{
}
