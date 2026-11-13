<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Validation\DispatchRevalidationService;
use App\Modules\Booking\Models\Booking;
use App\Modules\Customers\Models\NotificationDelivery;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM03-04 (Revalidation at dispatch / §16.2):
 * Proves that notifications scheduled for entities that subsequently become cancelled
 * abort cleanly with error_code 'entity_state_invalidated'.
 */
final class DispatchRevalidationTest extends TenantIntegrationTestCase
{
    public function test_revalidation_cancels_dispatch_if_booking_was_cancelled(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        // 1. Create a booking that is CANCELLED
        $booking = Booking::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 6001,
            'package_id' => 'pkg_satyanarayan',
            'city_id' => 'blr',
            'status' => 'CANCELLED',
            'total_price' => 5000,
            'advance_amount' => 1000,
            'scheduled_date' => now()->addDays(2),
        ]);

        $delivery = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 6001,
            'kind' => 'booking_reminder',
            'category' => 'REMINDER',
            'channel' => 'WHATSAPP',
            'provider' => 'whatsapp_cloud',
            'status' => NotificationDelivery::STATUS_QUEUED,
            'dedup_key' => 'dedup_reval_cancel_001',
            'intent_id' => (string) $booking->id,
        ]);

        $service = app(DispatchRevalidationService::class);
        $permitted = $service->revalidateAndPermit($delivery, 'booking', (string) $booking->id);

        $this->assertFalse($permitted);

        $delivery->refresh();
        $this->assertSame(NotificationDelivery::STATUS_CANCELLED, $delivery->status);
        $this->assertSame('entity_state_invalidated', $delivery->error_code);
    }

    public function test_revalidation_permits_dispatch_if_booking_is_active(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $booking = Booking::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 6002,
            'package_id' => 'pkg_satyanarayan',
            'city_id' => 'blr',
            'status' => 'CONFIRMED',
            'total_price' => 5000,
            'advance_amount' => 1000,
            'scheduled_date' => now()->addDays(2),
        ]);

        $delivery = NotificationDelivery::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => 6002,
            'kind' => 'booking_reminder',
            'category' => 'REMINDER',
            'channel' => 'WHATSAPP',
            'provider' => 'whatsapp_cloud',
            'status' => NotificationDelivery::STATUS_QUEUED,
            'dedup_key' => 'dedup_reval_active_002',
            'intent_id' => (string) $booking->id,
        ]);

        $service = app(DispatchRevalidationService::class);
        $permitted = $service->revalidateAndPermit($delivery, 'booking', (string) $booking->id);

        $this->assertTrue($permitted);
        $this->assertSame(NotificationDelivery::STATUS_QUEUED, $delivery->status);
    }
}

