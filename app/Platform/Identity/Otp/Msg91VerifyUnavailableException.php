<?php

namespace App\Platform\Identity\Otp;

/**
 * A token-level failure is a dependency fault, not a wrong code: the
 * challenge row must not burn an attempt (the manager's transaction
 * rolls back on any OtpProviderUnavailableException) and the client
 * sees a typed 503 — never a 401 oracle and never a channel downgrade.
 */
class Msg91VerifyUnavailableException extends OtpProviderUnavailableException
{
}
