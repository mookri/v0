<?php

namespace App\Modules\Temple\Contracts;

/**
 * Temple dashboard port (deptrac-blessed Contracts surface).
 * Ambient tenant context scopes every call.
 *
 * Booking-backed sections (S14) resolve each assignment's puja_id to a
 * catalog item through the catalog read port and key every booking read
 * by that item's sku through the booking read port; money columns are
 * booked value at this temple's unit price (assignment override, else
 * catalog list price) — bookings store no amount until the payments
 * slice. Unresolvable puja references fail closed to zeros/empty rows.
 *
 * @phpstan-type TempleDashboardOverview array{total_pujas: int, active_pujas: int, total_bookings: int, upcoming_bookings: int, revenue_minor: int, avg_occupancy: float, recent_bookings: list<array{id: string, puja_name: string, user_name: string, start_at: string, status: string}>}
 * @phpstan-type TempleDashboardPuja array{id: string, puja_id: string, puja_name: string, price_override_minor: int|null, capacity: int|null, schedule_json: string|null, status: string, bookings_count: int}
 * @phpstan-type TempleDashboardBooking array{id: string, puja_name: string, user_name: string, user_email: string, start_at: string, end_at: string, status: string, amount_minor: int}
 * @phpstan-type TempleCalendarDay array{date: string, slots: list<array{id: string, puja_name: string, start: string, end: string, capacity: int, booked: int}>}
 * @phpstan-type TempleAnalytics array{revenue_by_month: list<array{month: string, revenue_minor: int, bookings: int}>, occupancy_by_puja: list<array{puja_name: string, occupancy: float, bookings: int}>, peak_hours: list<array{hour: int, bookings: int}>}
 * @phpstan-type TempleSettings array{temple: array{id: string, slug: string, name: string, trust_name: string, type: string, address: string, city: string, state: string, lat: float|null, lng: float|null, images: list<string>, amenities: list<string>, description: string|null, status: string, verification_status: string}, managers: list<array{id: string, user_id: string, role: string, invited_at: string, accepted_at: string|null, user: array{id: string, name: string, email: string}|null}>, amenities: list<string>}
 * @phpstan-type TemplePage array{limit: int, has_more: bool, next_cursor: string|null}
 */
interface ProvidesTempleDashboard
{
    /**
     * Dashboard overview: KPIs and recent activity. total_bookings counts
     * lifetime rows for this temple's pujas (DRAFT carts excluded);
     * upcoming_bookings adds "scheduled now or later with a live status";
     * revenue_minor is lifetime booked value at this temple's unit price;
     * avg_occupancy is booked ÷ slot capacity for the current calendar
     * month (0.0 when nothing is scheduled); recent_bookings are the five
     * newest rows regardless of status (start_at is '' when unscheduled).
     *
     * @return array{total_pujas: int, active_pujas: int, total_bookings: int, upcoming_bookings: int, revenue_minor: int, avg_occupancy: float, recent_bookings: list<array{id: string, puja_name: string, user_name: string, start_at: string, status: string}>}
     */
    public function overview(string $templeId): array;

    /**
     * Pujas for dashboard with inline edit capability. bookings_count is
     * the lifetime booking count for the assignment's sku (DRAFT carts
     * excluded; 0 when the puja reference does not resolve); puja_name
     * resolves through the catalog item ('Puja' when unresolved).
     *
     * @param  array<string, mixed>  $filters
     * @param  string|null  $cursor  opaque cursor from a previous page (null = first page)
     * @return array{items: list<array{id: string, puja_id: string, puja_name: string, price_override_minor: int|null, capacity: int|null, schedule_json: string|null, status: string, bookings_count: int}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function pujas(string $templeId, array $filters = [], int $limit = 20, ?string $cursor = null): array;

    /**
     * Bookings for dashboard: rows for this temple's pujas (newest first,
     * platform cursor ordering). amount_minor is this temple's unit price
     * for the row's puja (assignment override, else catalog list price);
     * user identity is hydrated from the platform users table; start_at /
     * end_at are '' when a row has no schedule.
     *
     * @param  array<string, mixed>  $filters
     * @param  string|null  $cursor  opaque cursor from a previous page (null = first page)
     * @return array{items: list<array{id: string, puja_name: string, user_name: string, user_email: string, start_at: string, end_at: string, status: string, amount_minor: int}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function bookings(string $templeId, array $filters = [], int $limit = 20, ?string $cursor = null): array;

    /**
     * Calendar view: slots for a given month. booked counts committed
     * bookings intersecting each slot window [start, end) (DRAFT carts
     * and the terminal-bad cancelled set excluded); capacity is the slot
     * capacity, else the assignment capacity (min 1).
     *
     * @return array{items: list<array{date: string, slots: list<array{id: string, puja_name: string, start: string, end: string, capacity: int, booked: int}>}>}
     */
    public function calendar(string $templeId, int $year, int $month): array;

    /**
     * Analytics: revenue, occupancy trends over a rolling window ending
     * with the current month (clamped to 1–24 months). revenue_by_month
     * is zero-filled 'Y-m' buckets of booked value at this temple's unit
     * price; occupancy_by_puja is Σbooked ÷ Σcapacity over the window's
     * slots (0.0 when slotless; values above 1.0 mean overbooking);
     * peak_hours lists hours with at least one booking, busiest first.
     *
     * @return array{revenue_by_month: list<array{month: string, revenue_minor: int, bookings: int}>, occupancy_by_puja: list<array{puja_name: string, occupancy: float, bookings: int}>, peak_hours: list<array{hour: int, bookings: int}>}
     */
    public function analytics(string $templeId, int $months = 12): array;

    /**
     * Settings: temple details, managers, amenities.
     *
     * @return array{temple: array{id: string, slug: string, name: string, trust_name: string, type: string, address: string, city: string, state: string, lat: float|null, lng: float|null, images: list<string>, amenities: list<string>, description: string|null, status: string, verification_status: string}, managers: list<array{id: string, user_id: string, role: string, invited_at: string, accepted_at: string|null, user: array{id: string, name: string, email: string}|null}>, amenities: list<string>}
     */
    public function settings(string $templeId): array;
}
