<?php

namespace App\Domain\Delivery\Prasad;

use App\Domain\Delivery\Models\NotificationOutbox;
use App\Domain\Delivery\Support\DedupKeyGenerator;

final class PrasadShipmentService
{
    public const STATUS_PACKED = 'PRASAD_PACKED';
    public const STATUS_HANDOVER = 'HANDOVER_TO_CARRIER';
    public const STATUS_DISPATCHED = 'DISPATCHED';
    public const STATUS_OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_FAILED = 'DELIVERY_FAILED';

    public const EVENT_PRASAD_DISPATCHED = 'prasad.dispatched';
    public const EVENT_PRASAD_OUT_FOR_DELIVERY = 'prasad.out_for_delivery';
    public const EVENT_PRASAD_DELIVERED = 'prasad.delivered';

    /**
     * Process shipment tracking webhook from logistics carrier (BlueDart, Delhivery, etc.)
     *
     * @param array{
     *     carrier: string,
     *     tracking_number: string,
     *     status: string,
     *     timestamp?: string,
     *     tracking_url?: string,
     *     digital_blessing_url?: string,
     *     revision?: int
     * } $trackingData
     */
    public function handleCarrierTrackingEvent(
        string $tenantId,
        string $bookingId,
        int|string $userId,
        array $trackingData
    ): ?NotificationOutbox {
        $status = strtoupper($trackingData['status']);
        $carrier = $trackingData['carrier'];
        $trackingNumber = $trackingData['tracking_number'];

        $eventType = match ($status) {
            self::STATUS_DISPATCHED, 'IN_TRANSIT' => self::EVENT_PRASAD_DISPATCHED,
            self::STATUS_OUT_FOR_DELIVERY => self::EVENT_PRASAD_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED => self::EVENT_PRASAD_DELIVERED,
            default => null,
        };

        // If not a customer-notifiable milestone (e.g. internal transit hub scan), do not send notification
        if ($eventType === null) {
            return null;
        }

        $revision = (int) ($trackingData['revision'] ?? 1);
        $dedupKey = DedupKeyGenerator::generate(
            tenantId: $tenantId,
            eventType: $eventType,
            recipient: (string) $userId,
            subjectEntity: "prasad:{$trackingNumber}:{$status}",
            revision: $revision
        );

        $templateKind = match ($eventType) {
            self::EVENT_PRASAD_DISPATCHED => 'prasad_dispatched',
            self::EVENT_PRASAD_OUT_FOR_DELIVERY => 'prasad_out_for_delivery',
            self::EVENT_PRASAD_DELIVERED => 'prasad_delivered',
        };

        return NotificationOutbox::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => "evt_prasad_{$trackingNumber}_{$status}_{$revision}",
            'aggregate_type' => 'prasad_shipment',
            'aggregate_id' => $trackingNumber,
            'event_type' => $eventType,
            'payload_json' => [
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'carrier' => $carrier,
                'tracking_number' => $trackingNumber,
                'tracking_url' => $trackingData['tracking_url'] ?? "https://track.navdhya.com/{$trackingNumber}",
                'digital_blessing_url' => $trackingData['digital_blessing_url'] ?? null,
                'status' => $status,
                'template_kind' => $templateKind,
                'dedup_key' => $dedupKey,
            ],
            'status' => NotificationOutbox::STATUS_PENDING,
            'attempt_count' => 0,
        ]);
    }
}

