<?php

namespace App\Domain\Delivery\Exceptions;

use DomainException;

final class IllegalStateTransitionException extends DomainException
{
    public function __construct(string $fromState, string $toState, ?string $reason = null)
    {
        $message = "Illegal delivery state transition from [{$fromState}] to [{$toState}].";
        if ($reason !== null) {
            $message .= " Reason: {$reason}";
        }

        parent::__construct($message);
    }
}

