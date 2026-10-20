<?php

namespace App\Platform\Authz\Exceptions;

use RuntimeException;

/**
 * A policy payload violates the bounded declarative grammar (SEC-021,
 * LD-15): bad effect, capability selector shape, scope binding, or
 * condition clause. Rendered by the error envelope as 422
 * `validation_failed` with details.fields keyed by the offending field —
 * clients branch on the code, never the message.
 */
final class InvalidPolicyPayloadException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }
}
