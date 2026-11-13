<?php

namespace App\Domain\Delivery\Exceptions;

use RuntimeException;

final class ProviderNotConfiguredException extends RuntimeException
{
    public function __construct(string $providerOrKey, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Delivery provider is not configured: missing credentials for [{$providerOrKey}].",
            0,
            $previous
        );
    }
}

