<?php

namespace App\Platform\Identity\Otp;

/**
 * Fail-closed marker: the OTP provider a challenge needs is not
 * registered (or is down). Reachable only when a row references a
 * provider the app was not configured for — the verification fabric
 * never falls back to a different channel, so this surfaces as a typed
 * dependency error rather than an oracle-shaped 401 or a downgrade.
 *
 * Not final: a retryable provider refusal extends it (see
 * OtpProviderRateLimitedException) so fail-closed callers keep failing
 * closed without knowing the distinction.
 */
class OtpProviderUnavailableException extends \RuntimeException
{
}
