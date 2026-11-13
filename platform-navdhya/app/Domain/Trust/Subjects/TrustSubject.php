<?php

declare(strict_types=1);

namespace App\Domain\Trust\Subjects;

/**
 * Immutable server-derived subject reference (TS-08 / §17): resolved from
 * bookings & assignments server-side, never trusted from request payloads.
 */
final readonly class TrustSubject
{
    public function __construct(
        public TrustSubjectType $type,
        public string $id,
        public string $derivedFrom = 'server_assignment',
    ) {
        if (trim($id) === '') {
            throw new \InvalidArgumentException('TrustSubject id cannot be empty.');
        }
    }

    /**
     * @return array{type: string, id: string, derived_from: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'id' => $this->id,
            'derived_from' => $this->derivedFrom,
        ];
    }
}

