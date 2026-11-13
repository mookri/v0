<?php

namespace App\Domain\Calendar\Exceptions;

use RuntimeException;

final class PlaceUnresolvedException extends RuntimeException
{
    public function __construct(public readonly string $placeIdentifier)
    {
        parent::__construct("Unable to resolve place identifier '{$placeIdentifier}' to verified coordinates.");
    }
}

