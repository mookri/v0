<?php

namespace App\Domain\Verification\Exceptions;

use RuntimeException;

final class AppealIndependenceViolationException extends RuntimeException
{
    public readonly string $errorCode;

    public readonly int $statusCode;

    public function __construct(string $message, string $errorCode = 'appeal_decider_conflict', int $statusCode = 403)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
    }
}

