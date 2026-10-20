<?php

namespace App\Platform\Authz\Exceptions;

/**
 * SoD runtime denial (SEC-011 §7.3): a granted capability is refused at
 * decision time because the membership's resulting permission set trips
 * a blocking conflict rule involving the exercised permission (guards
 * against rules added after assignment). Renders as 403 `sod_conflict`
 * with the blocking rule codes — never the generic denial code, so
 * clients can distinguish policy denials from duty conflicts.
 */
final class SodConflictException extends AuthorizationDeniedException
{
    /**
     * @param list<string> $rules
     */
    public function __construct(
        string $capability,
        public readonly array $rules,
        string $message = 'This action violates separation of duties.',
    ) {
        parent::__construct($capability, 'sod_conflict', $message);
    }
}
