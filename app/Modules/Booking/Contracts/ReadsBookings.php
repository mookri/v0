<?php

namespace App\Modules\Booking\Contracts;

/**
 * Booking read port (deptrac-blessed Contracts surface): assignment and
 * review consumers resolve booking windows and reviews without importing
 * booking models. Ambient tenant context scopes every call.
 */
interface ReadsBookings
{
    /**
     * Booking windows by id; ids that do not resolve inside the ambient
     * tenant are omitted (never guessed at).
     *
     * @param  list<string>  $ids
     * @return array<string, array{id: string, user_id: int, service_sku: string|null, status: string, scheduled_at: string|null, duration_minutes: int}>
     */
    public function windowsByIds(array $ids): array;

    /**
     * Reviews for the given bookings, newest first (empty when none).
     *
     * @param  list<string>  $bookingIds
     * @return list<array{id: string, booking_id: string, rating: int, comment: string|null, created_at: string|null}>
     */
    public function reviewsForBookings(array $bookingIds, int $limit = 100): array;

    /**
     * Dashboard rollup for a set of service SKUs (consumers resolve
     * their own resources to SKUs first — the port never joins outward).
     * DRAFT rows are unconfirmed carts and never counted; total and
     * per-SKU counts are lifetime (any status except DRAFT). Upcoming =
     * scheduled at or after now with a live status (not DRAFT, not in
     * the terminal-bad cancelled set).
     *
     * @param  list<string>  $serviceSkus
     * @return array{total: int, upcoming: int, counts_by_sku: array<string, int>}
     */
    public function dashboardTotalsBySkus(array $serviceSkus): array;

    /**
     * Dashboard rows for a set of service SKUs: newest first (created_at
     * DESC — the platform cursor ordering), cursor-paginated (offset is
     * banned), user identity hydrated from the platform users table in a
     * second query (never a cross-table join). Rows carry no amount —
     * bookings store none; consumers price lines with their own
     * override/list price. Unknown SKUs simply match nothing.
     *
     * @param  list<string>  $serviceSkus
     * @param  array<string, mixed>  $filters  reads status (equality) + date_from/date_to (inclusive scheduled_at day bounds); non-string values are ignored
     * @param  int  $limit
     * @param  string|null  $cursor  opaque cursor from a previous page (null = first page)
     * @return array{items: list<array{id: string, service_sku: string, user_id: int, user_name: string, user_email: string, start_at: string|null, end_at: string|null, status: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function dashboardRowsBySkus(array $serviceSkus, array $filters = [], int $limit = 20, ?string $cursor = null): array;

    /**
     * Committed booking starts for a set of service SKUs inside an
     * optional window (inclusive from, exclusive to; null = unbounded).
     * DRAFT rows and the terminal-bad cancelled set never appear, and
     * rows without a start are unbucketable and excluded. Overlap and
     * bucketing arithmetic lives in the consumer.
     *
     * @param  list<string>  $serviceSkus
     * @param  string|null  $fromIso  inclusive ISO-8601 datetime
     * @param  string|null  $toIso  exclusive ISO-8601 datetime
     * @return list<array{service_sku: string, start_at: string, duration_minutes: int, status: string}>
     */
    public function scheduledStartsBySkus(array $serviceSkus, ?string $fromIso = null, ?string $toIso = null): array;
}
