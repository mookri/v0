<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * S14 temple-dashboard read ports (ReadsBookings): SKU rollups behind
 * the temple dashboard — totals/counts exclude DRAFT carts, rows hydrate
 * platform users in a second query and cursor-paginate (offset is
 * banned), scheduled starts are a bounded half-open window that never
 * carries DRAFT or terminal-bad rows. All reads are ambient-tenant
 * scoped; the clock is frozen so "upcoming" never drifts.
 */
final class BookingDashboardReadsTest extends TenantIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-08 12:00:00'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedBooking(int $userId, string $sku, array $overrides = []): Booking
    {
        return Booking::factory()->create([
            'user_id' => $userId,
            'service_sku' => $sku,
            ...$overrides,
        ]);
    }

    public function test_dashboard_totals_roll_up_by_sku_and_fail_closed_on_empty_input(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();

        $port = app(ReadsBookings::class);

        // Empty SKU list: zeros without touching the database.
        $empty = $port->dashboardTotalsBySkus([]);
        $this->assertSame(
            ['total' => 0, 'upcoming' => 0, 'counts_by_sku' => []],
            $empty,
        );

        // Confirmed future: total + upcoming + counted.
        $this->seedBooking($user->getKey(), 'S1', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-12 06:00:00'),
        ]);
        // Cancelled future: lifetime total only — terminal-bad is never upcoming.
        $this->seedBooking($user->getKey(), 'S1', [
            'status' => BookingLifecycle::STATUS_CANCELLED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-13 06:00:00'),
        ]);
        // DRAFT cart: excluded from everything.
        $this->seedBooking($user->getKey(), 'S1', [
            'status' => BookingLifecycle::STATUS_DRAFT,
            'scheduled_at' => CarbonImmutable::parse('2026-10-14 06:00:00'),
        ]);
        // Another sku: never rolled into S1's bucket.
        $this->seedBooking($user->getKey(), 'S2', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-15 06:00:00'),
        ]);

        $totals = $port->dashboardTotalsBySkus(['S1']);
        $this->assertSame(2, $totals['total']);
        $this->assertSame(1, $totals['upcoming']);
        $this->assertSame(['S1' => 2], $totals['counts_by_sku']);

        // Unknown SKUs simply match nothing (fail closed, no error).
        $none = $port->dashboardTotalsBySkus(['NOPE']);
        $this->assertSame(0, $none['total']);
        $this->assertSame(0, $none['upcoming']);
        $this->assertSame([], $none['counts_by_sku']);
    }

    public function test_dashboard_rows_hydrate_users_cursor_paginate_and_honor_filters(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $customer = User::factory()->create(['name' => 'Meera Joshi']);

        $port = app(ReadsBookings::class);

        $this->seedBooking($customer->getKey(), 'R1', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-20 06:00:00'),
            'created_at' => CarbonImmutable::parse('2026-10-01 09:00:00'),
        ]);
        $this->seedBooking($customer->getKey(), 'R1', [
            'status' => BookingLifecycle::STATUS_CANCELLED,
            'scheduled_at' => CarbonImmutable::parse('2026-09-25 06:00:00'),
            'created_at' => CarbonImmutable::parse('2026-10-02 09:00:00'),
        ]);
        $this->seedBooking($customer->getKey(), 'R1', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-21 06:00:00'),
            'created_at' => CarbonImmutable::parse('2026-10-03 09:00:00'),
        ]);

        // Newest created first, user identity from the platform users
        // table in a second query (never a join).
        $page = $port->dashboardRowsBySkus(['R1'], [], 2, null);
        $this->assertCount(2, $page['items']);
        $this->assertTrue($page['page']['has_more']);
        $this->assertNotNull($page['page']['next_cursor']);
        $this->assertSame('Meera Joshi', $page['items'][0]['user_name']);
        $this->assertSame($customer->email, $page['items'][0]['user_email']);
        $this->assertSame('CANCELLED', $page['items'][1]['status']);

        // Second page: no replay, no offset.
        $second = $port->dashboardRowsBySkus(
            ['R1'],
            [],
            2,
            $page['page']['next_cursor'],
        );
        $this->assertCount(1, $second['items']);
        $this->assertFalse($second['page']['has_more']);
        $this->assertSame(
            [],
            array_intersect(
                array_column($page['items'], 'id'),
                array_column($second['items'], 'id'),
            ),
        );

        // status equality + inclusive scheduled_at day bounds.
        $confirmed = $port->dashboardRowsBySkus(['R1'], ['status' => 'CONFIRMED'], 20, null);
        $this->assertCount(2, $confirmed['items']);

        $october = $port->dashboardRowsBySkus(
            ['R1'],
            ['date_from' => '2026-10-01', 'date_to' => '2026-10-31'],
            20,
            null,
        );
        $this->assertCount(2, $october['items']);

        // Non-string filter values (e.g. nulls from Request::only) are ignored.
        $ignored = $port->dashboardRowsBySkus(['R1'], ['status' => null], 20, null);
        $this->assertCount(3, $ignored['items']);

        // Unknown SKUs / empty input: empty page, never an error.
        $none = $port->dashboardRowsBySkus(['NOPE'], [], 20, null);
        $this->assertSame([], $none['items']);
        $this->assertFalse($none['page']['has_more']);
    }

    public function test_scheduled_starts_are_half_open_and_skip_draft_or_terminal_rows(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $user = User::factory()->create();

        $port = app(ReadsBookings::class);

        $windowStart = CarbonImmutable::parse('2026-10-12 06:00:00');
        $windowEnd = CarbonImmutable::parse('2026-10-12 07:00:00');

        // In-window committed booking: carried with duration.
        $this->seedBooking($user->getKey(), 'W1', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => $windowStart,
            'duration_minutes' => 60,
        ]);
        // Draft in-window: never carried.
        $this->seedBooking($user->getKey(), 'W1', [
            'status' => BookingLifecycle::STATUS_DRAFT,
            'scheduled_at' => CarbonImmutable::parse('2026-10-12 06:30:00'),
        ]);
        // Terminal-bad in-window: never carried.
        $this->seedBooking($user->getKey(), 'W1', [
            'status' => BookingLifecycle::STATUS_CANCELLED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-12 06:30:00'),
        ]);
        // Unscheduled: never carried.
        $this->seedBooking($user->getKey(), 'W1', ['scheduled_at' => null]);

        $starts = $port->scheduledStartsBySkus(
            ['W1'],
            $windowStart->toIso8601String(),
            $windowEnd->toIso8601String(),
        );

        $this->assertCount(1, $starts);
        $this->assertSame('W1', $starts[0]['service_sku']);
        $this->assertSame(60, $starts[0]['duration_minutes']);
        $this->assertSame('CONFIRMED', $starts[0]['status']);

        // Half-open [from, to): a start exactly at $to is outside.
        $toExclusive = $port->scheduledStartsBySkus(
            ['W1'],
            $windowStart->toIso8601String(),
            CarbonImmutable::parse('2026-10-12 06:00:00')->toIso8601String(),
        );
        $this->assertSame([], $toExclusive);

        // Unbounded call still skips DRAFT/terminal/unscheduled rows.
        $all = $port->scheduledStartsBySkus(['W1']);
        $this->assertCount(1, $all);

        // Empty SKU list: no query, no rows.
        $this->assertSame([], $port->scheduledStartsBySkus([]));
    }
}
