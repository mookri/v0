<?php

namespace App\Domain\Calendar\Exceptions;

use App\Domain\Calendar\Astronomy\HorizonStatusEnum;
use RuntimeException;

final class UnsupportedHorizonException extends RuntimeException
{
    public function __construct(
        public readonly HorizonStatusEnum $status,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $date,
        string $message = ''
    ) {
        $msg = $message !== '' ? $message : sprintf(
            'Cannot compute Panchang day for %s at (%F, %F): astronomical horizon is %s (P-05 polar refusal).',
            $date,
            $latitude,
            $longitude,
            $status->value
        );
        parent::__construct($msg);
    }
}

