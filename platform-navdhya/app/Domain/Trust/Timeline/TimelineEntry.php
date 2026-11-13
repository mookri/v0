<?php

declare(strict_types=1);

namespace App\Domain\Trust\Timeline;

use Carbon\CarbonImmutable;

final readonly class TimelineEntry
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $entityType,
        public string $entityId,
        public string $eventCode,
        public string $actorId,
        public string $visibilityScope,
        public string $summary,
        public array $metadata,
        public CarbonImmutable $occurredAt,
    ) {
    }
}

