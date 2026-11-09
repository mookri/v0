<?php

namespace App\Modules\Customers\Domain\Panchang\Exceptions;

use RuntimeException;

final class OutOfCorpusException extends RuntimeException
{
    public function __construct(string $message = 'Target astronomical anchor calculation falls outside the verified ephemeris corpus.')
    {
        parent::__construct($message);
    }
}
