<?php

namespace App\Platform\Authz\Contracts;

/**
 * Policy effect (SEC-021, K-009): allow or deny. Explicit deny always wins
 * in the SEC-022 precedence chain regardless of grant source.
 */
enum Effect: string
{
    case Allow = 'allow';
    case Deny = 'deny';
}
