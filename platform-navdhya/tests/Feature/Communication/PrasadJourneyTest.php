<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Models\NotificationOutbox;
use App\Domain\Delivery\Prasad\PrasadShipmentService;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM09-04:
 * Digital prasad tracking updates, milestone event generation,
 * and internal carrier hub scan filtering.
 */
final class PrasadJourneyTest extends TenantIntegrationTestCase
{
    public function test_dispatched_shipment_enqueues_customer_notification(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $service = new PrasadShipmentService();
        $outbox = $service->handleCarrierTrackingEvent(
            tenantId: $tenantId,
            bookingId: 'book_prasad_101',
            userId: 555,
            trackingData: [
                'carrier' => 'BlueDart',
                'tracking_number' => 'BD987654321',
                'status' => 'DISPATCHED',
                'tracking_url' => 'https://bluedart.com/track/BD987654321',
                'digital_blessing_url' => 'https://app.navdhya.com/blessing/video-101.mp4',
            ]
        );

        $this->assertInstanceOf(NotificationOutbox::class, $outbox);
        $this->assertSame(PrasadShipmentService::EVENT_PRASAD_DISPATCHED, $outbox->event_type);
        $this->assertSame('prasad_dispatched', $outbox->payload_json['template_kind']);
        $this->assertSame('BD987654321', $outbox->payload_json['tracking_number']);
        $this->assertNotEmpty($outbox->payload_json['digital_blessing_url']);
    }

    public function test_internal_carrier_hub_scan_is_ignored(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $service = new PrasadShipmentService();
        $outbox = $service->handleCarrierTrackingEvent(
            tenantId: $tenantId,
            bookingId: 'book_prasad_101',
            userId: 555,
            trackingData: [
                'carrier' => 'BlueDart',
                'tracking_number' => 'BD987654321',
                'status' => 'HUB_SORTING_FACILITY', // Non-customer milestone
            ]
        );

        $this->assertNull($outbox);
    }

    public function test_delivered_event_enqueues_delivery_notification(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $service = new PrasadShipmentService();
        $outbox = $service->handleCarrierTrackingEvent(
            tenantId: $tenantId,
            bookingId: 'book_prasad_101',
            userId: 555,
            trackingData: [
                'carrier' => 'Delhivery',
                'tracking_number' => 'DLH11223344',
                'status' => 'DELIVERED',
            ]
        );

        $this->assertInstanceOf(NotificationOutbox::class, $outbox);
        $this->assertSame(PrasadShipmentService::EVENT_PRASAD_DELIVERED, $outbox->event_type);
        $this->assertSame('prasad_delivered', $outbox->payload_json['template_kind']);
    }
}

