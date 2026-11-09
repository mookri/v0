<?php

namespace App\Modules\Customers\Outbox;

use App\Platform\Outbox\Outbox;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Tenancy\TenantContext;

/**
 * Customers module transactional outbox fact appender (E0).
 *
 * Dual-writes alongside AuditWriter:
 * - AuditWriter preserves tamper-evident staff compliance logs with hash chains.
 * - FactAppender appends domain events into the transactional outbox to drive
 *   asynchronous projections, telemetry ingestion, and notification daemons.
 */
final class FactAppender
{
    public function __construct(
        private readonly Outbox $outbox,
        private readonly TenantContext $context,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function append(
        string $eventName,
        int|string $userId,
        array $payload = [],
        ?string $tenantId = null,
    ): OutboxEvent {
        $resolvedTenantId = $tenantId ?? (string) $this->context->tenantId();

        return $this->outbox->append(
            $resolvedTenantId,
            'customer_profile',
            (string) $userId,
            $eventName,
            $payload,
        );
    }
}
