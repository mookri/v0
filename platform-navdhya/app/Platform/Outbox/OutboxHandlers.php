<?php

namespace App\Platform\Outbox;

/**
 * Handler registry for outbox events (F2). Producers never call
 * handlers directly; `outbox:drain` dispatches an event to the handler
 * registered for its event_type INSIDE the claim transaction, so the
 * handler's tenant-DB writes commit atomically with the dispatched_at
 * mark (replay-safe by construction).
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
    /** @var array<string, callable(OutboxEvent): void> */
    private array $handlers = [];

    /**
     * @param  callable(OutboxEvent): void  $handler
     */
    public function register(string $eventType, callable $handler): void
    {
        $this->handlers[$eventType] = $handler;
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
        ($this->handlers[$event->event_type])($event);
    }
}
