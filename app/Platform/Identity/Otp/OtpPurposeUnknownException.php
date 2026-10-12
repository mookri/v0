<?php

namespace App\Platform\Identity\Otp;

/**
 * Fail-closed marker: a purpose reached issuance without a row in the
 * purpose->policy table (config/otp-policy.php). Never folded into a
 * rate limit, a 401 or a default provider — an unmapped purpose is a
 * deployment error, not a user error.
 */
final class OtpPurposeUnknownException extends \RuntimeException
{
}
