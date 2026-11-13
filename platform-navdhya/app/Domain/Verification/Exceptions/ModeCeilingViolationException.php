<?php

namespace App\Domain\Verification\Exceptions;

use RuntimeException;

final class ModeCeilingViolationException extends RuntimeException
{
    public readonly string $errorCode;

    public readonly int $statusCode;

    public function __construct(string $message, string $errorCode = 'mode_ceiling_violation', int $statusCode = 422)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
    }
}

