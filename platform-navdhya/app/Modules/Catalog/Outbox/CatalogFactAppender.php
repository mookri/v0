<?php

namespace App\Modules\Catalog\Outbox;

use App\Platform\Outbox\Outbox;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Tenancy\TenantContext;

/**
 * Catalog Outbox Fact Appender (F0 / F4).
 * Dual-writes outbox facts for catalog mutations (publish, archive, price update, category move)
 * alongside AuditWriter records to feed consumer modules (Booking, Search, Telemetry).
 */
final class CatalogFactAppender
{
    public function __construct(
        private readonly Outbox $outbox,
        private readonly TenantContext $context,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function append(string $eventName, string $aggregateId, array $payload = []): OutboxEvent
    {
        $tenantId = (string) $this->context->tenantId();

        return $this->outbox->append(
            $tenantId,
            'catalog_service',
            $aggregateId,
            $eventName,
            $payload
        );
    }
}
