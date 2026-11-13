<?php

namespace App\Domain\Delivery\Listeners;

use App\Domain\Delivery\Events\PanditJourneyEvents;
use App\Domain\Delivery\Models\NotificationOutbox;
use App\Domain\Delivery\Support\DedupKeyGenerator;

final class PanditJourneyListener
{
    /**
     * Handle pandit journey transition and queue customer notification intent.
     *
     * @param array{
     *     delay_minutes?: int,
     *     eta?: string,
     *     pandit_name?: string,
     *     revision?: int
     * } $payload
     */
    public function handle(
        string $tenantId,
        string $eventType,
        string $bookingId,
        int|string $userId,
        array $payload = []
    ): ?NotificationOutbox {
        // Material delay filter (C-14): Ignore minor traffic fluctuations under 15m
        if ($eventType === PanditJourneyEvents::PANDIT_DELAYED) {
            $delayMinutes = (int) ($payload['delay_minutes'] ?? 0);
            if (! PanditJourneyEvents::isMaterialDelay($delayMinutes)) {
                return null;
            }
        }

        $revision = (int) ($payload['revision'] ?? 1);
        $dedupKey = DedupKeyGenerator::generate(
            tenantId: $tenantId,
            eventType: $eventType,
            recipient: (string) $userId,
            subjectEntity: "booking:{$bookingId}",
            revision: $revision
        );

        $templateKind = match ($eventType) {
            PanditJourneyEvents::PANDIT_DEPARTED => 'pandit_departed',
            PanditJourneyEvents::PANDIT_ARRIVED => 'pandit_arrived',
            PanditJourneyEvents::PANDIT_DELAYED => 'pandit_delayed',
            PanditJourneyEvents::PANDIT_ASSIGNED => 'pandit_assigned',
            default => 'pandit_journey_update',
        };

        return NotificationOutbox::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => "evt_{$eventType}_{$bookingId}_{$revision}",
            'aggregate_type' => 'booking',
            'aggregate_id' => $bookingId,
            'event_type' => $eventType,
            'payload_json' => array_merge($payload, [
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'template_kind' => $templateKind,
                'dedup_key' => $dedupKey,
            ]),
            'status' => NotificationOutbox::STATUS_PENDING,
            'attempt_count' => 0,
        ]);
    }
}
