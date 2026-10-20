<?php

namespace App\Platform\Identity;

use RuntimeException;

/**
 * MFA attempt lockout (M4): raised by MfaService once a user crosses the
 * failure threshold or presents anything while locked. Controllers map it
 * to 429 + Retry-After (contract §8) — deliberately distinct from the
 * collapsed 401 so a locked-out human learns to wait.
 */
final class MfaLockedException extends RuntimeException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct('MFA attempts locked; retry after '.$retryAfter.' s.');
    }
}
