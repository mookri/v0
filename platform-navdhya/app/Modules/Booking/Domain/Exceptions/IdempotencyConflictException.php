<?php

namespace App\Modules\Booking\Domain\Exceptions;

use RuntimeException;

final class IdempotencyConflictException extends RuntimeException
{
}
