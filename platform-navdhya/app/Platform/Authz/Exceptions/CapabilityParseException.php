<?php

namespace App\Platform\Authz\Exceptions;

use RuntimeException;

/**
 * A capability code failed to parse into <module>:<resource>:<action>
 * (SEC-020). Sync fails closed on this with the named error code
 * `capability_parse_failed` (AC-T-0K01-01-1). The offending code is
 * carried on capabilityCode (not Exception::$code, which PHP reserves
 * for an integer).
 */
final class CapabilityParseException extends RuntimeException
{
    public function __construct(
        public readonly string $capabilityCode,
        string $message,
    ) {
        parent::__construct(sprintf("capability '%s': %s", $capabilityCode, $message));
    }
}
