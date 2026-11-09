<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Application\CancelBookingUseCase;
use App\Modules\Booking\Application\RescheduleBookingUseCase;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingChange;
use App\Modules\Booking\Models\ResourceAllocation;
use App\Modules\Booking\Models\ResourceCalendar;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

final class BookingChangeUseCaseTest extends TenantIntegrationTestCase
{
    public function test_cancellation_policy_lead_time_tiers(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);
        try {
            $now = CarbonImmutable::now('UTC');

            // Tier 1: > 72h notice -> 0 fee, 100% refund
            $b1 = Booking::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'user_id' => $user->id,
                'service_sku' => 'sku-1',
                'status' => BookingLifecycle::STATUS_CONFIRMED,
                'total_minor' => 10000,
                'currency' => 'INR',
                'scheduled_at' => $now->addHours(100),
                'version' => 1,
            ]);

            // Tier 2: 48h - 72h notice -> 50% fee, 50% refund
            $b2 = Booking::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'user_id' => $user->id,
                'service_sku' => 'sku-2',
                'status' => BookingLifecycle::STATUS_CONFIRMED,
                'total_minor' => 10000,
                'currency' => 'INR',
                'scheduled_at' => $now->addHours(50),
                'version' => 1,
            ]);

            // Tier 3: < 48h notice -> 100% fee, 0% refund
            $b3 = Booking::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'user_id' => $user->id,
                'service_sku' => 'sku-3',
                'status' => BookingLifecycle::STATUS_CONFIRMED,
                'total_minor' => 10000,
                'currency' => 'INR',
                'scheduled_at' => $now->addHours(24),
                'version' => 1,
            ]);

            $useCase = app(CancelBookingUseCase::class);

            // Execute Tier 1
            $r1 = $useCase->execute((string) $tenant->getKey(), (string) $b1->getKey(), $user->id);
            $this->assertSame(0, $r1['fee_minor']);
            $this->assertSame(10000, $r1['refund_minor']);
            $this->assertSame(BookingLifecycle::STATUS_CANCELLED, $r1['booking']->status);

            // Execute Tier 2
            $r2 = $useCase->execute((string) $tenant->getKey(), (string) $b2->getKey(), $user->id);
            $this->assertSame(5000, $r2['fee_minor']);
            $this->assertSame(5000, $r2['refund_minor']);

            // Execute Tier 3
            $r3 = $useCase->execute((string) $tenant->getKey(), (string) $b3->getKey(), $user->id);
            $this->assertSame(10000, $r3['fee_minor']);
            $this->assertSame(0, $r3['refund_minor']);

            // Check booking changes recorded
            $this->assertDatabaseHas('booking_changes', [
                'booking_id' => (string) $b2->getKey(),
                'kind' => BookingChange::KIND_CANCELLATION,
                'fee_minor' => 5000,
                'refund_minor' => 5000,
            ], 'tenant_shared');
        } finally {
            $this->clearContext();
        }
    }

    public function test_reschedule_creates_revision_and_swaps_allocations(): void
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
                'resource_id' => 'pandit_resched_1',
                'timezone' => 'UTC',
                'is_active' => true,
            ]);

            $booking = Booking::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'user_id' => $user->id,
                'service_sku' => 'sku-puja',
                'status' => BookingLifecycle::STATUS_CONFIRMED,
                'scheduled_at' => $now->addDays(2),
                'duration_minutes' => 60,
                'version' => 1,
            ]);

            $alloc = ResourceAllocation::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'calendar_id' => $calendar->id,
                'booking_id' => $booking->id,
                'interval_start' => $now->addDays(2),
                'interval_end' => $now->addDays(2)->addHour(),
                'units' => 1,
                'status' => ResourceAllocation::STATUS_COMMITTED,
            ]);

            $useCase = app(RescheduleBookingUseCase::class);

            $newStart = $now->addDays(5);
            $result = $useCase->execute(
                (string) $tenant->getKey(),
                (string) $booking->getKey(),
                $user->id,
                $newStart,
                1,
                'User requested alternate date'
            );

            $this->assertSame(2, $result['booking']->version);
            $this->assertSame($newStart->toDateTimeString(), $result['booking']->scheduled_at->toDateTimeString());

            // Check revision created
            $this->assertDatabaseHas('booking_revisions', [
                'booking_id' => (string) $booking->getKey(),
                'version' => 1,
            ], 'tenant_shared');

            // Old allocation marked RELEASED
            $this->assertSame(
                ResourceAllocation::STATUS_RELEASED,
                $alloc->fresh()->status
            );

            // New allocation created with STATUS_COMMITTED
            $this->assertDatabaseHas('resource_allocations', [
                'booking_id' => (string) $booking->getKey(),
                'calendar_id' => $calendar->id,
                'status' => ResourceAllocation::STATUS_COMMITTED,
                'interval_start' => $newStart->toDateTimeString(),
            ], 'tenant_shared');
        } finally {
            $this->clearContext();
        }
    }
}
