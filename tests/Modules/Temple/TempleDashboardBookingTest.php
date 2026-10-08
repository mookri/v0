<?php

namespace Tests\Modules\Temple;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TemplePuja;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Temple dashboard Booking integration (S14): the dashboard's KPIs,
 * booking rows, calendar buckets and analytics run through the Booking
 * and Catalog read ports — assignment.puja_id resolves to a catalog
 * item (sku, name, list price), that sku keys every booking read, money
 * is the temple's own unit price (assignment override, else list), and
 * unresolvable puja references fail closed to zeros/empty rows.
 *
 * The clock is frozen at 2026-10-08 (a Thursday) so month buckets,
 * "upcoming" and weekday math never drift: October 2026 Mondays are
 * the 5th/12th/19th/26th, September's the 7th/14th/21st/28th.
 */
final class TempleDashboardBookingTest extends TenantIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-08 12:00:00'));
    }

    /**
     * @return array{0: Tenant, 1: array<string, string>}
     */
    private function actingInTenant(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);

        return [$tenant, $this->tenantHeaders($tenant)];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedTemple(Tenant $tenant, array $overrides = []): Temple
    {
        return Temple::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'status' => Temple::STATUS_ACTIVE,
            'verification_status' => Temple::VERIFICATION_VERIFIED,
            ...$overrides,
        ]);
    }

    /**
     * Catalog item + assignment wired the way production links them:
     * temple_pujas.puja_id = catalog item id. One Monday window
     * (day 1, 06:00–07:00, capacity 10) keeps the occupancy math
     * closed-form: four Mondays per month in the frozen window.
     *
     * @param  array<string, mixed>  $itemOverrides
     * @param  array<string, mixed>  $assignmentOverrides
     * @return array{0: CatalogItem, 1: TemplePuja}
     */
    private function seedPuja(Tenant $tenant, Temple $temple, string $sku, array $itemOverrides = [], array $assignmentOverrides = []): array
    {
        $catalogItem = CatalogItem::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'sku' => $sku,
            ...$itemOverrides,
        ]);

        $assignment = TemplePuja::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'temple_id' => (string) $temple->getKey(),
            'puja_id' => (string) $catalogItem->getKey(),
            'status' => TemplePuja::STATUS_ACTIVE,
            'price_override_minor' => null,
            'capacity' => null,
            'schedule_json' => [1 => [['start' => '06:00', 'end' => '07:00', 'capacity' => 10]]],
            ...$assignmentOverrides,
        ]);

        return [$catalogItem, $assignment];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedBooking(Tenant $tenant, User $customer, string $sku, array $overrides = []): Booking
    {
        return Booking::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $customer->getAuthIdentifier(),
            'service_sku' => $sku,
            ...$overrides,
        ]);
    }

    public function test_overview_rolls_up_bookings_through_the_catalog_join(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);
        $customer = User::factory()->create(['name' => 'Priya Sharma']);

        $this->seedPuja($tenant, $temple, 'DASH-LAKSHMI', [
            'name' => 'Griha Pravesh',
            'price_minor' => 50000,
        ]);

        // Confirmed future booking: total, upcoming, revenue, recent.
        $this->seedBooking($tenant, $customer, 'DASH-LAKSHMI', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-12 06:00:00'),
            'duration_minutes' => 60,
            'created_at' => CarbonImmutable::parse('2026-10-06 09:00:00'),
        ]);

        // Cancelled: lifetime total only — terminal-bad rows are never
        // upcoming and never revenue.
        $this->seedBooking($tenant, $customer, 'DASH-LAKSHMI', [
            'status' => BookingLifecycle::STATUS_CANCELLED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-19 06:00:00'),
            'created_at' => CarbonImmutable::parse('2026-10-05 09:00:00'),
        ]);

        // DRAFT cart: excluded from every KPI (still visible in the
        // recent-activity feed — it is activity).
        $this->seedBooking($tenant, $customer, 'DASH-LAKSHMI', [
            'status' => BookingLifecycle::STATUS_DRAFT,
            'scheduled_at' => CarbonImmutable::parse('2026-10-13 06:00:00'),
            'created_at' => CarbonImmutable::parse('2026-10-07 09:00:00'),
        ]);

        // Another sku this temple does not assign: never counted.
        $this->seedBooking($tenant, $customer, 'OTHER-SKU', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-14 06:00:00'),
            'created_at' => CarbonImmutable::parse('2026-10-04 09:00:00'),
        ]);

        // Another tenant's booking on the same sku: never counted.
        $foreignTenant = $this->makeTenant();
        $this->seedBooking($foreignTenant, $customer, 'DASH-LAKSHMI', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-15 06:00:00'),
        ]);

        $response = $this->getJson(
            '/api/v1/temple/partner/temples/'.$temple->getKey().'/dashboard/overview',
            $headers,
        );

        $response->assertOk()
            ->assertJsonPath('data.total_pujas', 1)
            ->assertJsonPath('data.active_pujas', 1)
            ->assertJsonPath('data.total_bookings', 2)
            ->assertJsonPath('data.upcoming_bookings', 1)
            ->assertJsonPath('data.revenue_minor', 50000);

        // Avg occupancy: 1 committed booking ÷ (4 October Mondays × 10).
        $response->assertJsonPath('data.avg_occupancy', 0.025);

        $recent = $response->json('data.recent_bookings');
        $this->assertCount(3, $recent);
        $this->assertSame('DRAFT', $recent[0]['status']);
        $this->assertSame('Griha Pravesh', $recent[0]['puja_name']);
        $this->assertSame('Priya Sharma', $recent[0]['user_name']);
        $this->assertSame('CONFIRMED', $recent[1]['status']);
        $this->assertNotSame('', $recent[1]['start_at']);
        $this->assertSame('CANCELLED', $recent[2]['status']);
    }

    public function test_dashboard_bookings_page_carries_rows_priced_at_the_temple_rate(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);
        $customer = User::factory()->create(['name' => 'Anita Devi']);

        // Item list price 45000 loses to the assignment override 70000.
        $this->seedPuja($tenant, $temple, 'RATE-PUJA', [
            'name' => 'Satyanarayan Puja',
            'price_minor' => 45000,
        ], [
            'price_override_minor' => 70000,
        ]);

        foreach ([['2026-10-01 09:00:00', '2026-10-20 06:00:00'], ['2026-10-02 09:00:00', '2026-10-21 06:00:00'], ['2026-10-03 09:00:00', '2026-10-22 06:00:00']] as [$createdAt, $scheduledAt]) {
            $this->seedBooking($tenant, $customer, 'RATE-PUJA', [
                'status' => BookingLifecycle::STATUS_CONFIRMED,
                'scheduled_at' => CarbonImmutable::parse($scheduledAt),
                'duration_minutes' => 60,
                'created_at' => CarbonImmutable::parse($createdAt),
            ]);
        }

        // Cancelled + last month: filtered out by date_from, visible by status.
        $this->seedBooking($tenant, $customer, 'RATE-PUJA', [
            'status' => BookingLifecycle::STATUS_CANCELLED,
            'scheduled_at' => CarbonImmutable::parse('2026-09-25 06:00:00'),
            'created_at' => CarbonImmutable::parse('2026-09-30 09:00:00'),
        ]);

        $url = '/api/v1/temple/partner/temples/'.$temple->getKey().'/dashboard/bookings';

        // Cursor walk: 4 rows at limit 2, newest created first, no replay.
        $pageOne = $this->getJson($url.'?limit=2', $headers)->assertOk();
        $this->assertCount(2, $pageOne->json('data'));
        $this->assertTrue($pageOne->json('meta.page.has_more'));
        $cursor = $pageOne->json('meta.page.next_cursor');
        $this->assertNotNull($cursor);

        $pageTwo = $this->getJson($url.'?limit=2&cursor='.urlencode((string) $cursor), $headers)->assertOk();
        $this->assertCount(2, $pageTwo->json('data'));
        $this->assertFalse($pageTwo->json('meta.page.has_more'));
        $this->assertSame(
            [],
            array_intersect(
                array_column($pageOne->json('data'), 'id'),
                array_column($pageTwo->json('data'), 'id'),
            ),
        );

        $first = $pageOne->json('data.0');
        $this->assertSame('Satyanarayan Puja', $first['puja_name']);
        $this->assertSame('Anita Devi', $first['user_name']);
        $this->assertSame($customer->email, $first['user_email']);
        $this->assertSame('CONFIRMED', $first['status']);
        $this->assertSame(70000, $first['amount_minor']);
        $this->assertNotSame('', $first['start_at']);

        // end_at = start_at + duration_minutes.
        $start = CarbonImmutable::parse($first['start_at']);
        $end = CarbonImmutable::parse($first['end_at']);
        $this->assertTrue($end->equalTo($start->addMinutes(60)));

        // status filter narrows to the cancelled row.
        $cancelledOnly = $this->getJson($url.'?status=CANCELLED', $headers)->assertOk();
        $this->assertCount(1, $cancelledOnly->json('data'));
        $this->assertSame('CANCELLED', $cancelledOnly->json('data.0.status'));

        // date_from drops last month's row.
        $october = $this->getJson($url.'?date_from=2026-10-01', $headers)->assertOk();
        $this->assertCount(3, $october->json('data'));
    }

    public function test_calendar_buckets_committed_bookings_into_slot_windows(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);
        $customer = User::factory()->create();

        $this->seedPuja($tenant, $temple, 'CAL-PUJA', ['name' => 'Griha Pravesh']);

        $this->seedBooking($tenant, $customer, 'CAL-PUJA', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-12 06:00:00'),
            'duration_minutes' => 60,
        ]);

        // In-window but terminal-bad: never consumes capacity.
        $this->seedBooking($tenant, $customer, 'CAL-PUJA', [
            'status' => BookingLifecycle::STATUS_CANCELLED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-19 06:00:00'),
            'duration_minutes' => 60,
        ]);

        // In-window draft: never consumes capacity either.
        $this->seedBooking($tenant, $customer, 'CAL-PUJA', [
            'status' => BookingLifecycle::STATUS_DRAFT,
            'scheduled_at' => CarbonImmutable::parse('2026-10-26 06:00:00'),
            'duration_minutes' => 60,
        ]);

        $response = $this->getJson(
            '/api/v1/temple/partner/temples/'.$temple->getKey().'/dashboard/calendar?year=2026&month=10',
            $headers,
        );

        $response->assertOk();

        $days = $response->json('data');
        $this->assertCount(4, $days);

        // October Mondays only (day-of-week schedule key 1).
        $this->assertSame(
            ['2026-10-05', '2026-10-12', '2026-10-19', '2026-10-26'],
            array_column($days, 'date'),
        );

        $this->assertSame('Griha Pravesh', $days[1]['slots'][0]['puja_name']);
        $this->assertSame(10, $days[1]['slots'][0]['capacity']);
        $this->assertSame(1, $days[1]['slots'][0]['booked']);
        $this->assertSame(0, $days[0]['slots'][0]['booked']);
        $this->assertSame(0, $days[2]['slots'][0]['booked']);
        $this->assertSame(0, $days[3]['slots'][0]['booked']);
    }

    public function test_analytics_reports_booked_value_occupancy_and_peak_hours(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);
        $customer = User::factory()->create();

        $this->seedPuja($tenant, $temple, 'ANL-PUJA', [
            'name' => 'Griha Pravesh',
            'price_minor' => 50000,
        ]);

        $this->seedBooking($tenant, $customer, 'ANL-PUJA', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-09-14 06:00:00'),
            'duration_minutes' => 60,
        ]);
        $this->seedBooking($tenant, $customer, 'ANL-PUJA', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-12 06:00:00'),
            'duration_minutes' => 60,
        ]);
        $this->seedBooking($tenant, $customer, 'ANL-PUJA', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-26 06:00:00'),
            'duration_minutes' => 60,
        ]);
        // Terminal-bad: no revenue, no occupancy, no peak-hour credit.
        $this->seedBooking($tenant, $customer, 'ANL-PUJA', [
            'status' => BookingLifecycle::STATUS_CANCELLED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-19 06:00:00'),
            'duration_minutes' => 60,
        ]);

        $response = $this->getJson(
            '/api/v1/temple/partner/temples/'.$temple->getKey().'/dashboard/analytics?months=2',
            $headers,
        );

        $response->assertOk();

        // Zero-filled 'Y-m' buckets, oldest first, booked value at the
        // temple's unit price (list price here — no override).
        $this->assertSame([
            ['month' => '2026-09', 'revenue_minor' => 50000, 'bookings' => 1],
            ['month' => '2026-10', 'revenue_minor' => 100000, 'bookings' => 2],
        ], $response->json('data.revenue_by_month'));

        // 3 booked ÷ (4 September + 4 October Mondays × 10 capacity).
        $this->assertSame([
            ['puja_name' => 'Griha Pravesh', 'occupancy' => 0.0375, 'bookings' => 3],
        ], $response->json('data.occupancy_by_puja'));

        $this->assertSame([
            ['hour' => 6, 'bookings' => 3],
        ], $response->json('data.peak_hours'));
    }

    public function test_dashboard_pujas_report_booking_counts_and_catalog_names(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);
        $customer = User::factory()->create();

        [$item, $resolved] = $this->seedPuja($tenant, $temple, 'COUNT-PUJA', ['name' => 'Satyanarayan Puja']);

        // Unresolvable reference: no catalog row behind this puja_id.
        $unresolved = TemplePuja::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'temple_id' => (string) $temple->getKey(),
            'puja_id' => 'puja_missing',
            'status' => TemplePuja::STATUS_ACTIVE,
            'price_override_minor' => null,
            'capacity' => null,
            'schedule_json' => null,
        ]);

        $this->seedBooking($tenant, $customer, 'COUNT-PUJA', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-12 06:00:00'),
        ]);
        $this->seedBooking($tenant, $customer, 'COUNT-PUJA', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-13 06:00:00'),
        ]);
        // DRAFT carts never count.
        $this->seedBooking($tenant, $customer, 'COUNT-PUJA', [
            'status' => BookingLifecycle::STATUS_DRAFT,
            'scheduled_at' => CarbonImmutable::parse('2026-10-14 06:00:00'),
        ]);

        $response = $this->getJson(
            '/api/v1/temple/partner/temples/'.$temple->getKey().'/dashboard/pujas',
            $headers,
        );

        $response->assertOk();
        $rows = collect($response->json('data'));

        $resolvedRow = $rows->firstWhere('id', (string) $resolved->getKey());
        $this->assertNotNull($resolvedRow);
        $this->assertSame((string) $item->getKey(), $resolvedRow['puja_id']);
        $this->assertSame('Satyanarayan Puja', $resolvedRow['puja_name']);
        $this->assertSame(2, $resolvedRow['bookings_count']);

        $unresolvedRow = $rows->firstWhere('id', (string) $unresolved->getKey());
        $this->assertNotNull($unresolvedRow);
        $this->assertSame('Puja', $unresolvedRow['puja_name']);
        $this->assertSame(0, $unresolvedRow['bookings_count']);
    }

    public function test_unresolvable_puja_references_fail_closed_to_empty_sections(): void
    {
        [$tenant, $headers] = $this->actingInTenant();
        $temple = $this->seedTemple($tenant);
        $customer = User::factory()->create();

        TemplePuja::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'temple_id' => (string) $temple->getKey(),
            'puja_id' => 'puja_missing',
            'status' => TemplePuja::STATUS_ACTIVE,
            'price_override_minor' => null,
            'capacity' => null,
            'schedule_json' => [1 => [['start' => '06:00', 'end' => '07:00', 'capacity' => 10]]],
        ]);

        // A booking that could match nothing: the join never resolves.
        $this->seedBooking($tenant, $customer, 'ORPHAN-SKU', [
            'status' => BookingLifecycle::STATUS_CONFIRMED,
            'scheduled_at' => CarbonImmutable::parse('2026-10-12 06:00:00'),
        ]);

        $base = '/api/v1/temple/partner/temples/'.$temple->getKey().'/dashboard';

        $overview = $this->getJson($base.'/overview', $headers);
        $overview->assertOk()
            ->assertJsonPath('data.total_bookings', 0)
            ->assertJsonPath('data.upcoming_bookings', 0)
            ->assertJsonPath('data.revenue_minor', 0)
            // 0.0 on the wire collapses to JSON 0 (zero floats encode as ints).
            ->assertJsonPath('data.avg_occupancy', 0)
            ->assertJsonPath('data.recent_bookings', []);

        $bookings = $this->getJson($base.'/bookings', $headers);
        $bookings->assertOk()->assertJsonPath('data', []);

        // Slots still render (the assignment exists) but bucket nothing
        // and fall back to the 'Puja' name.
        $calendar = $this->getJson($base.'/calendar?year=2026&month=10', $headers)->assertOk();
        $days = $calendar->json('data');
        $this->assertCount(4, $days);
        $this->assertSame('Puja', $days[0]['slots'][0]['puja_name']);
        foreach ($days as $day) {
            foreach ($day['slots'] as $slot) {
                $this->assertSame(0, $slot['booked']);
            }
        }

        $analytics = $this->getJson($base.'/analytics?months=2', $headers)->assertOk();
        $analytics->assertJsonPath('data.occupancy_by_puja', [])
            ->assertJsonPath('data.peak_hours', []);
    }

    public function test_unknown_tenant_temple_sections_fail_closed(): void
    {
        [$tenant, $headers] = $this->actingInTenant();

        // A real temple — in another tenant: scoped lookups never see it.
        $foreignTenant = $this->makeTenant();
        $foreignTemple = $this->seedTemple($foreignTenant);

        $base = '/api/v1/temple/partner/temples/'.$foreignTemple->getKey().'/dashboard';

        // overview resolves the temple row → 404 (parity: not_found).
        $this->getJson($base.'/overview', $headers)->assertNotFound();

        // The scope-query sections answer 200 with empty data: row-shaped
        // sections are [], while the analytics grid stays month-shaped
        // (zero-filled, exactly what a temple with no bookings returns).
        $this->getJson($base.'/bookings', $headers)->assertOk()->assertJsonPath('data', []);
        $this->getJson($base.'/calendar?year=2026&month=10', $headers)->assertOk()->assertJsonPath('data', []);

        $analytics = $this->getJson($base.'/analytics', $headers)->assertOk();
        $analytics->assertJsonPath('data.occupancy_by_puja', [])
            ->assertJsonPath('data.peak_hours', []);

        $grid = $analytics->json('data.revenue_by_month');
        $this->assertCount(12, $grid);
        foreach ($grid as $bucket) {
            $this->assertSame(0, $bucket['revenue_minor']);
            $this->assertSame(0, $bucket['bookings']);
        }
    }
}
