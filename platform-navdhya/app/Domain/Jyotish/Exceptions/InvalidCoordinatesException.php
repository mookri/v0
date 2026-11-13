<?php

namespace App\Domain\Jyotish\Exceptions;

use InvalidArgumentException;

final class InvalidCoordinatesException extends InvalidArgumentException
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        string $message = 'Geographic coordinates outside valid range (-90 <= lat <= 90, -180 <= lon <= 180).'
    ) {
        parent::__construct($message);
    }
}

