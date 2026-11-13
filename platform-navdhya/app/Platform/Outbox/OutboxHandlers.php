<?php

namespace App\Platform\Outbox;

/**
 * Handler registry for outbox events (F2). Producers never call
 * handlers directly; `outbox:drain` dispatches an event to all named
 * consumers registered for its event_type in registration order INSIDE
 * the claim transaction. Their same-connection tenant-DB writes commit
 * atomically with the dispatched_at mark; any failure propagates and
 * rolls back the whole claim (all consumers replay on retry).
 *
 * Contract for handlers:
 *  - tenant-DB writes only inside the handler (they ride the claim tx);
 *  - anything that cannot ride it (queued mail, HTTP) must be idempotent
 *    on the outbox event id — a commit failure after the side effect
 *    happened replays the handler once (at-least-once at that boundary);
 *  - an event_type with no registered handler is left pending untouched
 *    (no attempts burned), so consumers may land later.
 */
final class OutboxHandlers
{
    /** @var array<string, array<string, callable(OutboxEvent): void>> */
    private array $handlers = [];

    /**
     * @param  callable(OutboxEvent): void  $handler
     */
    public function register(string $eventType, string $consumerId, callable $handler): void
    {
        if (isset($this->handlers[$eventType][$consumerId])) {
            throw new \LogicException("Outbox consumer '{$consumerId}' is already registered for event type '{$eventType}'.");
        }

        $this->handlers[$eventType][$consumerId] = $handler;
    }

    public function has(string $eventType): bool
    {
        return isset($this->handlers[$eventType]);
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return array_keys($this->handlers);
    }

    public function dispatch(OutboxEvent $event): void
    {
        if (! $this->has($event->event_type)) {
            throw new \LogicException("No outbox consumers registered for event type '{$event->event_type}'.");
        }

        $payload = $event->payload ?? [];
        $completedConsumers = $payload['_completed_consumers'] ?? [];

        foreach ($this->handlers[$event->event_type] as $consumerId => $handler) {
            if (in_array($consumerId, $completedConsumers, true)) {
                // Already succeeded for this consumer on an earlier attempt
                continue;
            }

            try {
                $handler($event);
                $completedConsumers[] = $consumerId;
                $payload['_completed_consumers'] = $completedConsumers;
                $event->payload = $payload;
            } catch (\Throwable $e) {
                $event->payload = $payload;
                throw $e;
            }
        }
    }
}
