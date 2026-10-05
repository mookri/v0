<?php

namespace App\Modules\Temple\Contracts;

/**
 * Temple dashboard port (deptrac-blessed Contracts surface).
 * Ambient tenant context scopes every call.
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
     * Dashboard overview: KPIs and recent activity.
     *
     * @return array{total_pujas: int, active_pujas: int, total_bookings: int, upcoming_bookings: int, revenue_minor: int, avg_occupancy: float, recent_bookings: list<array{id: string, puja_name: string, user_name: string, start_at: string, status: string}>}
     */
    public function overview(string $templeId): array;

    /**
     * Pujas for dashboard with inline edit capability.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, puja_id: string, puja_name: string, price_override_minor: int|null, capacity: int|null, schedule_json: string|null, status: string, bookings_count: int}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function pujas(string $templeId, array $filters = [], int $limit = 20): array;

    /**
     * Bookings for dashboard.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, puja_name: string, user_name: string, user_email: string, start_at: string, end_at: string, status: string, amount_minor: int}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function bookings(string $templeId, array $filters = [], int $limit = 20): array;

    /**
     * Calendar view: slots for a given month.
     *
     * @return array{items: list<array{date: string, slots: list<array{id: string, puja_name: string, start: string, end: string, capacity: int, booked: int}>}>}
     */
    public function calendar(string $templeId, int $year, int $month): array;

    /**
     * Analytics: revenue, occupancy trends.
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
