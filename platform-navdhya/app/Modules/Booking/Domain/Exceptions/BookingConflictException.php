<?php

namespace App\Modules\Booking\Domain\Exceptions;

use RuntimeException;

final class BookingConflictException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly string $errorCode = 'booking_conflict',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

