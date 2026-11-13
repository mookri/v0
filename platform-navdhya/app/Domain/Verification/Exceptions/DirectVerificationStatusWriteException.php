<?php

namespace App\Domain\Verification\Exceptions;

use RuntimeException;

final class DirectVerificationStatusWriteException extends RuntimeException
{
    public static function forModel(string $modelClass): self
    {
        return new self(sprintf(
            'Direct mutation of verification_status on %s is forbidden (T-01). Use VerificationProjector.',
            $modelClass,
        ));
    }
}
