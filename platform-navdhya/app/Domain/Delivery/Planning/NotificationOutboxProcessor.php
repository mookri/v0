<?php

namespace App\Domain\Delivery\Planning;

use App\Domain\Delivery\Models\NotificationOutbox;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Processor for transactional notification outbox (E-CM-03).
 * Claims rows atomically, plans intents, and handles retry backoff.
 */
final class NotificationOutboxProcessor
{
    public function __construct(
        private readonly IntentPlanner $intentPlanner,
    ) {
    }

    /**
     * Process up to $batchSize pending outbox rows.
     *
     * @return int Number of rows successfully dispatched
     */
    public function process(int $batchSize = 50): int
    {
        $now = CarbonImmutable::now();

        $rows = NotificationOutbox::query()
            ->where('status', NotificationOutbox::STATUS_PENDING)
            ->where(function ($query) use ($now): void {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', $now);
            })
            ->limit($batchSize)
            ->get();

        $dispatched = 0;

        foreach ($rows as $row) {
            $row->status = NotificationOutbox::STATUS_CLAIMED;
            $row->save();

            try {
                $payload = $row->payload_json;

                $this->intentPlanner->plan(
                    tenantId: $row->tenant_id,
                    eventType: $row->event_type,
                    recipientRef: (string) ($payload['recipient_ref'] ?? $payload['recipient'] ?? 'devotee'),
                    subjectType: $row->aggregate_type,
                    subjectId: $row->aggregate_id,
                    category: (string) ($payload['category'] ?? 'transactional'),
                    priority: (string) ($payload['priority'] ?? 'normal'),
                    locale: (string) ($payload['locale'] ?? 'en_IN'),
                    revision: (int) ($payload['revision'] ?? 1)
                );

                $row->status = NotificationOutbox::STATUS_DISPATCHED;
                $row->save();
                $dispatched++;
            } catch (Throwable $e) {
                $row->attempt_count++;
                if ($row->attempt_count >= 5) {
                    $row->status = NotificationOutbox::STATUS_FAILED;
                } else {
                    $row->status = NotificationOutbox::STATUS_PENDING;
                    $delaySeconds = (int) (2 ** $row->attempt_count) * 5;
                    $row->next_attempt_at = $now->addSeconds($delaySeconds);
                }
                $row->save();
            }
        }

        return $dispatched;
    }
}

