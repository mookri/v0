<?php

namespace App\Platform\Outbox;

/**
 * Transactional outbox writer (F2). append() MUST be called inside the
 * producer's transaction on the same tenant connection: the event row
 * then commits or rolls back atomically with the state change it
 * describes — the whole point of the outbox (no dual-write window).
 *
 * Side effects that cannot join that transaction (mail, SSE, other
 * services) never happen here: they are handlers registered on
 * OutboxHandlers and driven by `outbox:drain`.
 */
final class Outbox
{
    /**
     * @param  string  $tenantId  explicit on purpose: the fact belongs to
     *                            one tenant regardless of ambient scope
     * @param  array<string, mixed>  $payload
     */
    public function append(
        string $tenantId,
        string $aggregateType,
        string $aggregateId,
        string $eventType,
        array $payload,
    ): OutboxEvent {
        $event = new OutboxEvent();
        $event->forceFill([
            'tenant_id' => $tenantId,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'payload' => $payload,
        ]);
        $event->save();

        return $event;
    }
}
