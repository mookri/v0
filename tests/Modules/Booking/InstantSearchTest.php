<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditAvailability;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TemplePuja;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Instant-book search (B2): one endpoint — date + slot (+ optional puja)
 * → assignable pandits and open temple slots. Covers the containment
 * rule (whole slot must fit a window), the fail-closed assignment
 * conflict exclusion, VERIFIED + ACTIVE visibility on both sides, the
 * puja filter, validation, and the route's middleware posture. Cross-
 * module reads happen only through Contracts ports (deptrac) — this
 * test exercises the seam end-to-end.
 */
final class InstantSearchTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User, 2: array<string, string>}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);

        return [$tenant, $user, $this->tenantHeaders($tenant)];
    }

    /**
     * A date far enough ahead to be safely "after_or_equal: today" in
     * every run, with helpers for the weekday and slot strings.
     *
     * @return array{date: string, weekday: int}
     */
    private function searchDate(): array
    {
        $date = CarbonImmutable::now()->addDays(7);

        return ['date' => $date->toDateString(), 'weekday' => (int) $date->format('w')];
    }

    private function seedPandit(Tenant $tenant, array $overrides = []): PanditProfile
    {
        $this->resolveContext($tenant);

        try {
            return PanditProfile::factory()->create($overrides);
        } finally {
            $this->clearContext();
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedAvailability(Tenant $tenant, string $panditId, int $day, array $overrides = []): PanditAvailability
    {
        $this->resolveContext($tenant);

        try {
            return PanditAvailability::factory()->create(array_merge([
                'pandit_id' => $panditId,
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'slot_duration_minutes' => 60,
                'is_recurring' => true,
                'specific_date' => null,
            ], $overrides));
        } finally {
            $this->clearContext();
        }
    }

    private function seedTemple(Tenant $tenant, array $overrides = []): Temple
    {
        $this->resolveContext($tenant);

        try {
            return Temple::factory()->create(array_merge([
                'tenant_id' => (string) $tenant->getKey(),
                'verification_status' => Temple::VERIFICATION_VERIFIED,
                'status' => Temple::STATUS_ACTIVE,
            ], $overrides));
        } finally {
            $this->clearContext();
        }
    }

    /**
     * @param  list<array{start: string, end: string, capacity?: int}>  $windows
     * @param  array<string, mixed>  $overrides
     */
    private function seedTemplePuja(Tenant $tenant, string $templeId, string $pujaId, int $day, array $windows, array $overrides = []): TemplePuja
    {
        $this->resolveContext($tenant);

        try {
            return TemplePuja::factory()->create(array_merge([
                'tenant_id' => (string) $tenant->getKey(),
                'temple_id' => $templeId,
                'puja_id' => $pujaId,
                'status' => TemplePuja::STATUS_ACTIVE,
                'schedule_json' => json_encode([(string) $day => $windows]),
            ], $overrides));
        } finally {
            $this->clearContext();
        }
    }

    private function seedBusyAssignment(Tenant $tenant, string $panditId, string $when): PanditAssignment
    {
        $this->resolveContext($tenant);

        try {
            $booking = Booking::factory()->create([
                'scheduled_at' => $when,
                'duration_minutes' => 60,
            ]);

            return PanditAssignment::factory()->create([
                'pandit_id' => $panditId,
                'booking_id' => (string) $booking->getKey(),
                'status' => PanditAssignment::STATUS_CONFIRMED,
            ]);
        } finally {
            $this->clearContext();
        }
    }

    public function test_validation_rejects_bad_input(): void
    {
        [, , $headers] = $this->customer();
        $date = $this->searchDate();

        // Missing everything.
        $this->assertEnvelopeCode(
            $this->getJson('/api/v1/booking/instant-search', $headers),
            422,
            'validation_failed',
        );

        // Past date.
        $this->assertEnvelopeCode(
            $this->getJson('/api/v1/booking/instant-search?'.http_build_query([
                'date' => CarbonImmutable::now()->subDay()->toDateString(),
                'start' => '10:00',
                'end' => '11:00',
            ]), $headers),
            422,
            'validation_failed',
        );

        // Bad time format.
        $this->assertEnvelopeCode(
            $this->getJson('/api/v1/booking/instant-search?'.http_build_query([
                'date' => $date['date'],
                'start' => '10:00:00',
                'end' => '11:00',
            ]), $headers),
            422,
            'validation_failed',
        );

        // end before start.
        $this->assertEnvelopeCode(
            $this->getJson('/api/v1/booking/instant-search?'.http_build_query([
                'date' => $date['date'],
                'start' => '11:00',
                'end' => '10:00',
            ]), $headers),
            422,
            'validation_failed',
        );

        // Limit out of range.
        $this->assertEnvelopeCode(
            $this->getJson('/api/v1/booking/instant-search?'.http_build_query([
                'date' => $date['date'],
                'start' => '10:00',
                'end' => '11:00',
                'limit' => 0,
            ]), $headers),
            422,
            'validation_failed',
        );
    }

    public function test_returns_assignable_pandits_and_open_temple_slots(): void
    {
        [$tenant, , $headers] = $this->customer();
        $date = $this->searchDate();

        // Assignable: verified + active, weekday window covers the slot.
        $assignable = $this->seedPandit($tenant, ['display_name' => 'Assignable Acharya']);
        $this->seedAvailability($tenant, (string) $assignable->getKey(), $date['weekday']);

        // Temple with two windows, both on the search weekday.
        $temple = $this->seedTemple($tenant, ['name' => 'Sri Ram Temple']);
        $pujaId = (string) \Illuminate\Support\Str::ulid();
        $this->seedTemplePuja($tenant, (string) $temple->getKey(), $pujaId, $date['weekday'], [
            ['start' => '06:00', 'end' => '08:00', 'capacity' => 5],
            ['start' => '10:00', 'end' => '12:00', 'capacity' => 7],
        ]);

        // Pandit: 10:00-11:00 sits inside the 09:00-17:00 window; temple
        // slot: inside the 10:00-12:00 window.
        $response = $this->getJson('/api/v1/booking/instant-search?'.http_build_query([
            'date' => $date['date'],
            'start' => '10:00',
            'end' => '11:00',
        ]), $headers)
            ->assertOk()
            ->assertJsonPath('data.object', 'instant_search')
            ->assertJsonPath('data.date', $date['date'])
            ->assertJsonPath('data.start', '10:00')
            ->assertJsonPath('data.end', '11:00')
            ->assertJsonStructure([
                'data' => ['object', 'date', 'start', 'end', 'puja_id', 'pandits', 'temple_slots'],
                'meta' => ['request_id'],
            ]);

        $panditIds = array_column($response->json('data.pandits'), 'id');
        $this->assertContains((string) $assignable->getKey(), $panditIds);
        $this->assertSame('Assignable Acharya', $response->json('data.pandits.0.display_name'));

        $slots = $response->json('data.temple_slots');
        $this->assertCount(1, $slots);
        $this->assertSame((string) $temple->getKey(), $slots[0]['temple_id']);
        $this->assertSame('Sri Ram Temple', $slots[0]['temple_name']);
        $this->assertSame($pujaId, $slots[0]['puja_id']);
        $this->assertSame('10:00', $slots[0]['start']);
        $this->assertSame('12:00', $slots[0]['end']);
        $this->assertSame(7, $slots[0]['capacity']);

        // The 06:00-08:00 window does not cover a 10:00 slot — only the
        // covering window surfaced.
        $starts = array_column($slots, 'start');
        $this->assertNotContains('06:00', $starts);
    }

    public function test_pandits_must_be_verified_active_available_and_fully_covered(): void
    {
        [$tenant, , $headers] = $this->customer();
        $date = $this->searchDate();
        $otherDay = ($date['weekday'] + 1) % 7;

        // Wrong weekday.
        $wrongDay = $this->seedPandit($tenant);
        $this->seedAvailability($tenant, (string) $wrongDay->getKey(), $otherDay);

        // Covers the slot partially (starts after the slot start).
        $lateStart = $this->seedPandit($tenant);
        $this->seedAvailability($tenant, (string) $lateStart->getKey(), $date['weekday'], [
            'start_time' => '10:30:00',
            'end_time' => '17:00:00',
        ]);

        // Covers the slot partially (ends before the slot end).
        $earlyEnd = $this->seedPandit($tenant);
        $this->seedAvailability($tenant, (string) $earlyEnd->getKey(), $date['weekday'], [
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
        ]);

        // Unverified profile (fails the public gate before any window math).
        $pending = $this->seedPandit($tenant, [
            'verification_status' => PanditProfile::VERIFICATION_PENDING,
        ]);
        $this->seedAvailability($tenant, (string) $pending->getKey(), $date['weekday']);

        // One-off window ON the date counts even though it is not recurring.
        $oneOff = $this->seedPandit($tenant);
        $this->seedAvailability($tenant, (string) $oneOff->getKey(), $date['weekday'], [
            'is_recurring' => false,
            'specific_date' => $date['date'],
        ]);

        $response = $this->getJson('/api/v1/booking/instant-search?'.http_build_query([
            'date' => $date['date'],
            'start' => '10:00',
            'end' => '11:00',
        ]), $headers)->assertOk();

        $panditIds = array_column($response->json('data.pandits'), 'id');

        $this->assertContains((string) $oneOff->getKey(), $panditIds);
        $this->assertNotContains((string) $wrongDay->getKey(), $panditIds);
        $this->assertNotContains((string) $lateStart->getKey(), $panditIds);
        $this->assertNotContains((string) $earlyEnd->getKey(), $panditIds);
        $this->assertNotContains((string) $pending->getKey(), $panditIds);
    }

    public function test_pandit_with_a_conflicting_assignment_is_excluded(): void
    {
        [$tenant, , $headers] = $this->customer();
        $date = $this->searchDate();

        // Busy pandit: confirmed assignment exactly on the requested slot.
        $busy = $this->seedPandit($tenant);
        $this->seedAvailability($tenant, (string) $busy->getKey(), $date['weekday']);
        $this->seedBusyAssignment($tenant, (string) $busy->getKey(), $date['date'].' 10:00:00');

        // Free pandit: confirmed assignment, but outside the slot.
        $free = $this->seedPandit($tenant);
        $this->seedAvailability($tenant, (string) $free->getKey(), $date['weekday']);
        $this->seedBusyAssignment($tenant, (string) $free->getKey(), $date['date'].' 14:00:00');

        $response = $this->getJson('/api/v1/booking/instant-search?'.http_build_query([
            'date' => $date['date'],
            'start' => '10:00',
            'end' => '11:00',
        ]), $headers)->assertOk();

        $panditIds = array_column($response->json('data.pandits'), 'id');

        $this->assertNotContains((string) $busy->getKey(), $panditIds);
        $this->assertContains((string) $free->getKey(), $panditIds);
    }

    public function test_puja_id_filters_temple_slots_and_invisible_temples_never_appear(): void
    {
        [$tenant, , $headers] = $this->customer();
        $date = $this->searchDate();

        $temple = $this->seedTemple($tenant, ['name' => 'Filter Temple']);
        $wanted = (string) \Illuminate\Support\Str::ulid();
        $other = (string) \Illuminate\Support\Str::ulid();

        // Two puja assignments on the same weekday window.
        $this->seedTemplePuja($tenant, (string) $temple->getKey(), $wanted, $date['weekday'], [
            ['start' => '10:00', 'end' => '11:30', 'capacity' => 3],
        ]);
        $this->seedTemplePuja($tenant, (string) $temple->getKey(), $other, $date['weekday'], [
            ['start' => '10:00', 'end' => '11:30', 'capacity' => 4],
        ]);

        // A temple that is verified but INACTIVE: invisible on both the
        // directory and the availability gate.
        $inactive = $this->seedTemple($tenant, ['status' => Temple::STATUS_INACTIVE]);
        $this->seedTemplePuja($tenant, (string) $inactive->getKey(), $wanted, $date['weekday'], [
            ['start' => '10:00', 'end' => '11:30', 'capacity' => 9],
        ]);

        $base = [
            'date' => $date['date'],
            'start' => '10:00',
            'end' => '11:00',
        ];

        // Unfiltered: both puja windows of the visible temple, nothing from
        // the inactive one.
        $all = $this->getJson('/api/v1/booking/instant-search?'.http_build_query($base), $headers)
            ->assertOk()
            ->json('data.temple_slots');

        $this->assertCount(2, $all);
        foreach ($all as $slot) {
            $this->assertSame((string) $temple->getKey(), $slot['temple_id']);
        }

        // Filtered: only the wanted puja's window.
        $filtered = $this->getJson('/api/v1/booking/instant-search?'.http_build_query(array_merge($base, [
            'puja_id' => $wanted,
        ])), $headers)
            ->assertOk()
            ->json('data.temple_slots');

        $this->assertCount(1, $filtered);
        $this->assertSame($wanted, $filtered[0]['puja_id']);

        // Unknown puja: no matches, never an error (search degrades to empty).
        $none = $this->getJson('/api/v1/booking/instant-search?'.http_build_query(array_merge($base, [
            'puja_id' => (string) \Illuminate\Support\Str::ulid(),
        ])), $headers)
            ->assertOk()
            ->json('data.temple_slots');

        $this->assertSame([], $none);
    }

    public function test_limit_caps_both_sides(): void
    {
        [$tenant, , $headers] = $this->customer();
        $date = $this->searchDate();

        $temple = $this->seedTemple($tenant);
        $this->seedTemplePuja($tenant, (string) $temple->getKey(), (string) \Illuminate\Support\Str::ulid(), $date['weekday'], [
            ['start' => '10:00', 'end' => '12:00', 'capacity' => 5],
            ['start' => '10:00', 'end' => '12:00', 'capacity' => 6],
            ['start' => '10:00', 'end' => '12:00', 'capacity' => 7],
        ]);

        for ($i = 0; $i < 3; $i++) {
            $pandit = $this->seedPandit($tenant);
            $this->seedAvailability($tenant, (string) $pandit->getKey(), $date['weekday']);
        }

        $response = $this->getJson('/api/v1/booking/instant-search?'.http_build_query([
            'date' => $date['date'],
            'start' => '10:00',
            'end' => '11:00',
            'limit' => 2,
        ]), $headers)->assertOk();

        $this->assertCount(2, $response->json('data.pandits'));
        $this->assertCount(2, $response->json('data.temple_slots'));
    }

    public function test_route_is_registered_with_the_expected_middleware(): void
    {
        $route = app('router')->getRoutes()->getByName('booking.instant-search');

        $this->assertNotNull($route, 'booking.instant-search route must be registered');
        $this->assertSame('GET', $route->methods()[0]);

        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth.any', $middleware);
        $this->assertContains('tenant', $middleware);
        $this->assertContains('scope:booking:read', $middleware);
        $this->assertContains('throttle:60,1', $middleware);
    }
}
