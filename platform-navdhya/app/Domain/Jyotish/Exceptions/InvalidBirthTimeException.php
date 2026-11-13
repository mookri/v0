<?php

namespace App\Domain\Jyotish\Exceptions;

use InvalidArgumentException;

final class InvalidBirthTimeException extends InvalidArgumentException
{
    public function __construct(
        public readonly string $localTime,
        public readonly string $timezone,
        string $message = 'Non-existent local birth time during DST spring forward gap.'
    ) {
        parent::__construct($message);
    }
}

