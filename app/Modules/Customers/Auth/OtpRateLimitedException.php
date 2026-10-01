<?php

namespace App\Modules\Customers\Auth;

use RuntimeException;

/**
 * Marker for DB-backed OTP rate caps (mirrors onboarding's exception so
 * controllers catch a module-local type — never a cross-module import).
 */
final class OtpRateLimitedException extends RuntimeException
{
}
