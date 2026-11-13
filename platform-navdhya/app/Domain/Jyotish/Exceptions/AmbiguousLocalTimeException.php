<?php

namespace App\Domain\Jyotish\Exceptions;

use RuntimeException;

final class AmbiguousLocalTimeException extends RuntimeException
{
    public function __construct(
        public readonly string $localTime,
        public readonly string $timezone,
        string $message = 'Local birth time falls within an ambiguous DST fallback fold.'
    ) {
        parent::__construct($message);
    }
}

