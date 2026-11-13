<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Events\PanditJourneyEvents;
use App\Domain\Delivery\Listeners\PanditJourneyListener;
use App\Domain\Delivery\Models\NotificationOutbox;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM09-01 (Closes C-14):
 * Pandit arrival journey event producers, material delay thresholding,
 * and outbox enqueuing with deterministic deduplication keys.
 */
final class PanditArrivalJourneyTest extends TenantIntegrationTestCase
{
    public function test_pandit_departed_enqueues_outbox_notification(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $listener = new PanditJourneyListener();
        $outbox = $listener->handle(
            tenantId: $tenantId,
            eventType: PanditJourneyEvents::PANDIT_DEPARTED,
            bookingId: 'book_123',
            userId: 456,
            payload: [
                'pandit_name' => 'Acharya Sharma',
                'eta' => '10:30 AM',
                'revision' => 1,
            ]
        );

        $this->assertInstanceOf(NotificationOutbox::class, $outbox);
        $this->assertSame(PanditJourneyEvents::PANDIT_DEPARTED, $outbox->event_type);
        $this->assertSame('pandit_departed', $outbox->payload_json['template_kind']);
        $this->assertNotEmpty($outbox->payload_json['dedup_key']);
    }

    public function test_immaterial_delay_under_15_minutes_is_suppressed(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $listener = new PanditJourneyListener();
        // 10 minutes delay (< 15 min threshold)
        $outbox = $listener->handle(
            tenantId: $tenantId,
            eventType: PanditJourneyEvents::PANDIT_DELAYED,
            bookingId: 'book_123',
            userId: 456,
            payload: [
                'delay_minutes' => 10,
                'eta' => '10:40 AM',
            ]
        );

        $this->assertNull($outbox);
        $this->assertDatabaseMissing('notification_outbox', [
            'tenant_id' => $tenantId,
            'event_type' => PanditJourneyEvents::PANDIT_DELAYED,
        ], 'tenant_shared');
    }

    public function test_material_delay_equal_or_over_15_minutes_enqueues_notification(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $listener = new PanditJourneyListener();
        // 20 minutes delay (>= 15 min threshold)
        $outbox = $listener->handle(
            tenantId: $tenantId,
            eventType: PanditJourneyEvents::PANDIT_DELAYED,
            bookingId: 'book_123',
            userId: 456,
            payload: [
                'delay_minutes' => 20,
                'eta' => '10:50 AM',
                'revision' => 2,
            ]
        );

        $this->assertInstanceOf(NotificationOutbox::class, $outbox);
        $this->assertSame(PanditJourneyEvents::PANDIT_DELAYED, $outbox->event_type);
        $this->assertSame('pandit_delayed', $outbox->payload_json['template_kind']);
        $this->assertSame(20, $outbox->payload_json['delay_minutes']);
    }
}
