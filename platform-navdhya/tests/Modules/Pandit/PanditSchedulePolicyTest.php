<?php

namespace Tests\Modules\Pandit;

use App\Models\User;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Pandit\Contracts\PanditSchedulePolicy;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditAvailability;
use App\Modules\Pandit\Models\PanditBlackout;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Pandit schedule policy test (Task B2): evaluates weekly working hours,
 * blackout periods, travel buffer intervals, and effective windows.
 */
final class PanditSchedulePolicyTest extends TenantIntegrationTestCase
{
    private function seedProfile(Tenant $tenant, array $overrides = []): PanditProfile
    {
        $this->resolveContext($tenant);
        try {
            $user = User::factory()->create();
            $this->makeMembership($tenant, $user);

            return PanditProfile::factory()->create(array_merge([
                'user_id' => $user->getAuthIdentifier(),
                'verification_status' => PanditProfile::VERIFICATION_VERIFIED,
                'status' => PanditProfile::STATUS_ACTIVE,
                'travel_buffer_minutes' => 45,
                'timezone' => 'Asia/Kolkata',
            ], $overrides));
        } finally {
            $this->clearContext();
        }
    }

    public function test_effective_window_calculation(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant, ['travel_buffer_minutes' => 45]);

        $this->resolveContext($tenant);
        try {
            /** @var PanditSchedulePolicy $policy */
            $policy = app(PanditSchedulePolicy::class);

            $start = CarbonImmutable::parse('2026-11-16 10:00:00', 'Asia/Kolkata');
            $end = CarbonImmutable::parse('2026-11-16 11:30:00', 'Asia/Kolkata');

            $window = $policy->getEffectiveWindow((string) $tenant->getKey(), (string) $profile->getKey(), $start, $end);

            $this->assertSame(45, $window->bufferMinutes);
            $this->assertSame('2026-11-16 09:15:00', $window->bufferedStart->toDateTimeString());
            $this->assertSame('2026-11-16 12:15:00', $window->bufferedEnd->toDateTimeString());
        } finally {
            $this->clearContext();
        }
    }

    public function test_available_when_slot_within_recurring_hours_and_no_conflicts(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant);

        // Monday 2026-11-16 (dayOfWeek = 1)
        $this->resolveContext($tenant);
        try {
            PanditAvailability::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'day_of_week' => 1,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'slot_duration_minutes' => 60,
                'is_recurring' => true,
            ]);

            /** @var PanditSchedulePolicy $policy */
            $policy = app(PanditSchedulePolicy::class);

            $start = CarbonImmutable::parse('2026-11-16 10:00:00', 'Asia/Kolkata');
            $end = CarbonImmutable::parse('2026-11-16 11:30:00', 'Asia/Kolkata');

            $verdict = $policy->isAvailableAt((string) $tenant->getKey(), (string) $profile->getKey(), $start, $end);
            $this->assertTrue($verdict->isAvailable());
        } finally {
            $this->clearContext();
        }
    }

    public function test_unavailable_when_outside_recurring_working_hours(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant);

        $this->resolveContext($tenant);
        try {
            PanditAvailability::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'day_of_week' => 1,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'slot_duration_minutes' => 60,
                'is_recurring' => true,
            ]);

            /** @var PanditSchedulePolicy $policy */
            $policy = app(PanditSchedulePolicy::class);

            // Attempt booking at 07:00 - 08:30 (before 09:00)
            $start = CarbonImmutable::parse('2026-11-16 07:00:00', 'Asia/Kolkata');
            $end = CarbonImmutable::parse('2026-11-16 08:30:00', 'Asia/Kolkata');

            $verdict = $policy->isAvailableAt((string) $tenant->getKey(), (string) $profile->getKey(), $start, $end);
            $this->assertFalse($verdict->isAvailable());
            $this->assertSame('outside_working_hours', $verdict->reason());
        } finally {
            $this->clearContext();
        }
    }

    public function test_unavailable_when_overlapping_blackout_period(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant, ['travel_buffer_minutes' => 30]);

        $this->resolveContext($tenant);
        try {
            $tz = 'Asia/Kolkata';
            PanditBlackout::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'starts_at' => CarbonImmutable::parse('2026-11-16 11:00:00', $tz)->utc()->toDateTimeString(),
                'ends_at' => CarbonImmutable::parse('2026-11-16 14:00:00', $tz)->utc()->toDateTimeString(),
                'reason' => 'Festival celebration',
                'type' => PanditBlackout::TYPE_FESTIVAL,
            ]);

            /** @var PanditSchedulePolicy $policy */
            $policy = app(PanditSchedulePolicy::class);

            // Candidate 10:00 - 11:30 IST intersects blackout starting at 11:00 IST
            $start = CarbonImmutable::parse('2026-11-16 10:00:00', $tz);
            $end = CarbonImmutable::parse('2026-11-16 11:30:00', $tz);

            $verdict = $policy->isAvailableAt((string) $tenant->getKey(), (string) $profile->getKey(), $start, $end);
            $this->assertFalse($verdict->isAvailable());
            $this->assertSame('blackout_period', $verdict->reason());
            $this->assertSame('Festival celebration', $verdict->conflicts()['reason'] ?? null);
        } finally {
            $this->clearContext();
        }
    }

    public function test_unavailable_when_overlapping_travel_buffer_of_another_assignment(): void
    {
        $tenant = $this->makeTenant();
        $profile = $this->seedProfile($tenant, ['travel_buffer_minutes' => 45]);

        // Mock ReadsBookings port to return an existing booking 09:00 - 10:00 UTC
        $bookingId = '01JQZZZZZZZZZZZZZZZZZZZZ01';
        $bookingsMock = $this->createMock(ReadsBookings::class);
        $bookingsMock->method('windowsByIds')->willReturn([
            $bookingId => [
                'id' => $bookingId,
                'user_id' => 1,
                'service_sku' => 'SATYANARAYAN_PUJA',
                'status' => 'CONFIRMED',
                'scheduled_at' => '2026-11-16T09:00:00Z',
                'duration_minutes' => 60,
            ],
        ]);
        $this->app->instance(ReadsBookings::class, $bookingsMock);

        $this->resolveContext($tenant);
        try {
            // Seed assignment for the existing booking
            PanditAssignment::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'pandit_id' => (string) $profile->getKey(),
                'booking_id' => $bookingId,
                'status' => PanditAssignment::STATUS_CONFIRMED,
                'compensation_minor' => 150000,
            ]);

            /** @var PanditSchedulePolicy $policy */
            $policy = app(PanditSchedulePolicy::class);

            // Existing booking is 09:00 - 10:00. With 45 min buffer, buffer window extends to 10:45.
            // Candidate slot is 10:15 - 11:15 (buffered start is 09:30).
            // Pure raw slots (09:00-10:00 vs 10:15-11:15) do NOT overlap.
            // But with travel buffer, overlap occurs!
            $start = CarbonImmutable::parse('2026-11-16T10:15:00Z');
            $end = CarbonImmutable::parse('2026-11-16T11:15:00Z');

            $verdict = $policy->isAvailableAt((string) $tenant->getKey(), (string) $profile->getKey(), $start, $end);
            $this->assertFalse($verdict->isAvailable());
            $this->assertSame('assignment_conflict', $verdict->reason());
            $this->assertSame($bookingId, $verdict->conflicts()['booking_id'] ?? null);
        } finally {
            $this->clearContext();
        }
    }
}
