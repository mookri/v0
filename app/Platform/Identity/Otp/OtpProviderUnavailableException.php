<?php

namespace App\Platform\Identity\Otp;

/**
 * Fail-closed marker: the OTP provider a challenge needs is not
 * registered (or is down). Reachable only when a row references a
 * provider the app was not configured for — the verification fabric
 * never falls back to a different channel, so this surfaces as a typed
 * dependency error rather than an oracle-shaped 401 or a downgrade.
 */
final class OtpProviderUnavailableException extends \RuntimeException
{
}
