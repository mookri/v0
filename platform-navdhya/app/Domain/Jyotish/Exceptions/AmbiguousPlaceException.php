<?php

namespace App\Domain\Jyotish\Exceptions;

use RuntimeException;

final class AmbiguousPlaceException extends RuntimeException
{
    /**
     * @param list<array{place_id: string, name_en: string, latitude: float, longitude: float, elevation_m: int, iana_timezone: string, is_verified: bool}> $candidates
     */
    public function __construct(
        public readonly string $query,
        public readonly array $candidates,
        string $message = 'Birthplace query matches multiple verified locations.'
    ) {
        parent::__construct($message);
    }
}

