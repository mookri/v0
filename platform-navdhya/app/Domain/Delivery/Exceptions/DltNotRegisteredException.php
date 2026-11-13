<?php

namespace App\Domain\Delivery\Exceptions;

use RuntimeException;

final class DltNotRegisteredException extends RuntimeException
{
    public function __construct(string $templateCode)
    {
        parent::__construct(
            "SMS dispatch rejected: template [{$templateCode}] has no approved TRAI DLT registration (fail-closed)."
        );
    }
}

