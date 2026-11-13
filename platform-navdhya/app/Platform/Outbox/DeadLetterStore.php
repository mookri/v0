<?php

namespace App\Platform\Outbox;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * DeadLetterStore (T-JYAUT-00e / D-16):
 * Manages quarantine and replay of exhausted or poisoned outbox events.
 */
final class DeadLetterStore
{
    /**
     * Quarantine an exhausted event into outbox_dead_letters table.
     */
    public function quarantine(OutboxEvent $event, ?string $error = null): OutboxDeadLetter
    {
        $deadLetter = new OutboxDeadLetter();
        $deadLetter->forceFill([
            'tenant_id' => $event->tenant_id,
            'original_event_id' => (string) $event->getKey(),
            'aggregate_type' => $event->aggregate_type,
            'aggregate_id' => $event->aggregate_id,
            'event_type' => $event->event_type,
            'payload' => $event->payload ?? [],
            'attempts' => $event->attempts,
            'last_error' => Str::limit($error ?? $event->last_error ?? 'Max retries exhausted', 490),
            'quarantined_at' => CarbonImmutable::now('UTC'),
        ]);
        $deadLetter->save();

        return $deadLetter;
    }

    /**
     * Replay a dead-letter event by resetting its attempts and clearing quarantine.
     */
    public function replay(string $deadLetterId): bool
    {
        $deadLetter = OutboxDeadLetter::query()->where('id', $deadLetterId)->first();
        if ($deadLetter === null || $deadLetter->replayed_at !== null) {
            return false;
        }

        $originalEvent = OutboxEvent::query()->where('id', $deadLetter->original_event_id)->first();
        if ($originalEvent !== null) {
            // Reset attempts so OutboxDrainCommand can reclaim and retry
            $originalEvent->forceFill([
                'attempts' => 0,
                'last_error' => null,
                'dispatched_at' => null,
            ])->save();
        }

        $deadLetter->forceFill(['replayed_at' => CarbonImmutable::now('UTC')])->save();

        return true;
    }

    /**
     * Replay all exhausted dead letters for a given tenant or globally.
     */
    public function replayAll(?string $tenantId = null): int
    {
        $query = OutboxDeadLetter::query()->whereNull('replayed_at');
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $deadLetters = $query->get();
        $count = 0;

        foreach ($deadLetters as $dl) {
            if ($this->replay((string) $dl->getKey())) {
                $count++;
            }
        }

        return $count;
    }
}

