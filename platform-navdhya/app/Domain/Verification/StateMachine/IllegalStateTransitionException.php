<?php

namespace App\Domain\Verification\StateMachine;

use RuntimeException;

final class IllegalStateTransitionException extends RuntimeException
{
    public readonly string $errorCode;

    public readonly int $statusCode;

    public function __construct(string $from, string $to)
    {
        $this->errorCode = 'state_forbidden';
        $this->statusCode = 409;
        parent::__construct(sprintf('Illegal onboarding transition from %s to %s.', $from, $to));
    }
}
