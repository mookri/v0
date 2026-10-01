<?php

namespace App\Platform\Authz\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * Kernel authorization denial (doc 06 §4.2, SEC-022).
 *
 * Thrown by the kernel authorizer when a capability check fails. The error
 * envelope renders it as 403 with the stable machine code
 * `authorization_denied`; clients branch on the code, never the message.
 * Carries the denied capability + deciding reason so the denial audit
 * record and (from T-0K01-03) the decision log can be written.
 */
final class AuthorizationDeniedException extends AuthorizationException
{
    public function __construct(
        public readonly string $capability,
        public readonly string $reason = 'default_deny',
        string $message = 'This action is unauthorized.',
    ) {
        parent::__construct($message);
    }
}
