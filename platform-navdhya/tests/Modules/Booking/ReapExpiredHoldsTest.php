<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\ResourceAllocation;
use App\Modules\Booking\Models\ResourceCalendar;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Tests\TenantIntegrationTestCase;

final class ReapExpiredHoldsTest extends TenantIntegrationTestCase
{
    public function test_reaps_expired_allocations_and_expires_pending_bookings(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);
        try {
            $now = CarbonImmutable::now('UTC');

            $calendar = ResourceCalendar::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'resource_type' => 'PANDIT',
                'resource_id' => 'pandit_reap_1',
                'timezone' => 'UTC',
                'is_active' => true,
            ]);

            // Expired booking with expired hold
            $expiredBooking = Booking::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'user_id' => $user->id,
                'service_sku' => 'puja-satyanarayan',
                'status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
                'scheduled_at' => $now->addDays(2),
                'checkout_expires_at' => $now->subMinutes(5),
                'version' => 1,
            ]);

            $expiredAlloc = ResourceAllocation::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'calendar_id' => $calendar->id,
                'booking_id' => $expiredBooking->id,
                'interval_start' => $now->addDays(2),
                'interval_end' => $now->addDays(2)->addHour(),
                'units' => 1,
                'status' => ResourceAllocation::STATUS_HELD,
                'expires_at' => $now->subMinutes(5),
            ]);

            // Active booking with future hold
            $activeBooking = Booking::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'user_id' => $user->id,
                'service_sku' => 'puja-rudrabhishek',
                'status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
                'scheduled_at' => $now->addDays(3),
                'checkout_expires_at' => $now->addMinutes(10),
                'version' => 1,
            ]);

            $activeAlloc = ResourceAllocation::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'calendar_id' => $calendar->id,
                'booking_id' => $activeBooking->id,
                'interval_start' => $now->addDays(3),
                'interval_end' => $now->addDays(3)->addHour(),
                'units' => 1,
                'status' => ResourceAllocation::STATUS_HELD,
                'expires_at' => $now->addMinutes(10),
            ]);
        } finally {
            $this->clearContext();
        }

        // Run command
        Artisan::call('booking:reap-expired-holds', ['--tenant' => (string) $tenant->getKey()]);

        $this->resolveContext($tenant);
        try {
            $this->assertSame(
                ResourceAllocation::STATUS_EXPIRED,
                $expiredAlloc->fresh()->status
            );
            $this->assertSame(
                BookingLifecycle::STATUS_EXPIRED,
                $expiredBooking->fresh()->status
            );

            // Active hold remains HELD and booking remains PENDING_PAYMENT
            $this->assertSame(
                ResourceAllocation::STATUS_HELD,
                $activeAlloc->fresh()->status
            );
            $this->assertSame(
                BookingLifecycle::STATUS_PENDING_PAYMENT,
                $activeBooking->fresh()->status
            );

            // Transition recorded
            $this->assertDatabaseHas('booking_transitions', [
                'booking_id' => (string) $expiredBooking->getKey(),
                'from_status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
                'to_status' => BookingLifecycle::STATUS_EXPIRED,
                'reason' => 'checkout_hold_timeout',
            ], 'tenant_shared');
        } finally {
            $this->clearContext();
        }
    }
}

