<?php

namespace App\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingReview;
use App\Modules\Booking\Models\PanditArrival;
use App\Platform\Api\CursorPagination;
use Illuminate\Http\Request;

/**
 * ReadsBookings port implementation: booking windows (schedule overlap
 * arithmetic lives in the consumer), per-booking reviews, and the
 * temple-dashboard SKU rollups — all ambient-tenant scoped like every
 * other booking query.
 */
final class BookingReads implements ReadsBookings
{
    /**
     * @param  list<string>  $ids
     * @return array<string, array{id: string, user_id: int, service_sku: string|null, status: string, scheduled_at: string|null, duration_minutes: int}>
     */
    public function windowsByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (string $id): bool => $id !== '')));

        if ($ids === []) {
            return [];
        }

        $windows = [];

        foreach (Booking::query()->whereIn('id', $ids)->get() as $booking) {
            $windows[(string) $booking->getKey()] = [
                'id' => (string) $booking->getKey(),
                'user_id' => (int) $booking->user_id,
                'service_sku' => $booking->service_sku,
                'status' => $booking->status,
                'scheduled_at' => $booking->scheduled_at?->toJSON(),
                'duration_minutes' => (int) $booking->duration_minutes,
            ];
        }

        return $windows;
    }

    /**
     * @param  list<string>  $bookingIds
     * @return list<array{id: string, booking_id: string, rating: int, comment: string|null, created_at: string|null}>
     */
    public function reviewsForBookings(array $bookingIds, int $limit = 100): array
    {
        $bookingIds = array_values(array_unique(array_filter($bookingIds, static fn (string $id): bool => $id !== '')));

        if ($bookingIds === []) {
            return [];
        }

        return BookingReview::query()
            ->whereIn('booking_id', $bookingIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(max(1, min(200, $limit)))
            ->get()
            ->map(static fn (BookingReview $review): array => [
                'id' => (string) $review->getKey(),
                'booking_id' => (string) $review->booking_id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toJSON(),
            ])
            ->all();
    }

    /**
     * @param  list<string>  $serviceSkus
     * @return array{total: int, upcoming: int, counts_by_sku: array<string, int>}
     */
    public function dashboardTotalsBySkus(array $serviceSkus): array
    {
        $skus = $this->cleanSkus($serviceSkus);

        if ($skus === []) {
            return ['total' => 0, 'upcoming' => 0, 'counts_by_sku' => []];
        }

        $countsBySku = [];
        $counts = Booking::query()
            ->whereIn('service_sku', $skus)
            ->where('status', '!=', BookingLifecycle::STATUS_DRAFT)
            ->groupBy('service_sku')
            ->selectRaw('service_sku, COUNT(*) as bookings_count')
            ->pluck('bookings_count', 'service_sku');

        foreach ($counts as $sku => $count) {
            $countsBySku[(string) $sku] = (int) $count;
        }

        $upcoming = Booking::query()
            ->whereIn('service_sku', $skus)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', \Carbon\CarbonImmutable::now())
            ->where('status', '!=', BookingLifecycle::STATUS_DRAFT)
            ->whereNotIn('status', BookingLifecycle::SECTIONS['cancelled'])
            ->count();

        return [
            'total' => array_sum($countsBySku),
            'upcoming' => $upcoming,
            'counts_by_sku' => $countsBySku,
        ];
    }

    /**
     * @param  list<string>  $serviceSkus
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array{id: string, service_sku: string, user_id: int, user_name: string, user_email: string, start_at: string|null, end_at: string|null, status: string}>, page: array{limit: int, has_more: bool, next_cursor: string|null}}
     */
    public function dashboardRowsBySkus(array $serviceSkus, array $filters = [], int $limit = 20, ?string $cursor = null): array
    {
        $query = Booking::query()->whereIn('service_sku', $this->cleanSkus($serviceSkus));

        $status = $filters['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $dateFrom = $filters['date_from'] ?? null;
        if (is_string($dateFrom) && $dateFrom !== '') {
            $query->whereDate('scheduled_at', '>=', $dateFrom);
        }

        $dateTo = $filters['date_to'] ?? null;
        if (is_string($dateTo) && $dateTo !== '') {
            $query->whereDate('scheduled_at', '<=', $dateTo);
        }

        [$rows, $page] = CursorPagination::paginate(
            $query,
            new Request(['limit' => $limit, 'cursor' => $cursor]),
            $limit,
        );

        // Hydrate user identity in one second query — never a cross-table join.
        $userIds = array_values(array_unique(array_map(
            static fn (Booking $booking): int => (int) $booking->user_id,
            $rows,
        )));

        $users = $userIds === []
            ? []
            : User::query()
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy(static fn (User $user): int => (int) $user->getKey());

        $items = [];
        foreach ($rows as $booking) {
            $start = $booking->scheduled_at;
            $end = $start !== null ? $start->copy()->addMinutes((int) $booking->duration_minutes) : null;
            $user = $users[(int) $booking->user_id] ?? null;

            $items[] = [
                'id' => (string) $booking->getKey(),
                'service_sku' => (string) $booking->service_sku,
                'user_id' => (int) $booking->user_id,
                'user_name' => $user->name ?? '',
                'user_email' => $user->email ?? '',
                'start_at' => $start?->toJSON(),
                'end_at' => $end?->toJSON(),
                'status' => $booking->status,
            ];
        }

        return ['items' => $items, 'page' => $page];
    }

    /**
     * @param  list<string>  $serviceSkus
     * @param  string|null  $fromIso
     * @param  string|null  $toIso
     * @return list<array{service_sku: string, start_at: string, duration_minutes: int, status: string}>
     */
    public function scheduledStartsBySkus(array $serviceSkus, ?string $fromIso = null, ?string $toIso = null): array
    {
        $skus = $this->cleanSkus($serviceSkus);

        if ($skus === []) {
            return [];
        }

        $query = Booking::query()
            ->whereIn('service_sku', $skus)
            ->whereNotNull('scheduled_at')
            ->where('status', '!=', BookingLifecycle::STATUS_DRAFT)
            ->whereNotIn('status', BookingLifecycle::SECTIONS['cancelled'])
            ->orderBy('scheduled_at')
            ->orderBy('id');

        if ($fromIso !== null) {
            $query->where('scheduled_at', '>=', \Carbon\Carbon::parse($fromIso));
        }

        if ($toIso !== null) {
            $query->where('scheduled_at', '<', \Carbon\Carbon::parse($toIso));
        }

        return $query->get()->map(static function (Booking $booking): array {
            $start = $booking->scheduled_at;

            return [
                'service_sku' => (string) $booking->service_sku,
                'start_at' => $start?->toJSON() ?? '',
                'duration_minutes' => (int) $booking->duration_minutes,
                'status' => $booking->status,
            ];
        })->all();
    }

    /**
     * Blank-safe SKU list: unique, non-empty, request order preserved.
     *
     * @param  list<string>  $serviceSkus
     * @return list<string>
     */
    private function cleanSkus(array $serviceSkus): array
    {
        return array_values(array_unique(array_filter(
            $serviceSkus,
            static fn (string $sku): bool => $sku !== '',
        )));
    }

    public function bookingSnapshotForTrust(string $bookingId): ?array
    {
        $booking = Booking::query()->where('id', $bookingId)->first();
        if ($booking === null) {
            return null;
        }

        return [
            'id' => (string) $booking->getKey(),
            'user_id' => (int) $booking->user_id,
            'service_sku' => $booking->service_sku,
            'status' => $booking->status,
            'scheduled_at' => $booking->scheduled_at?->toJSON(),
            'current_revision_id' => $booking->current_revision_id !== null ? (string) $booking->current_revision_id : null,
            'completed_at' => $booking->status === BookingLifecycle::STATUS_COMPLETED
                ? $booking->updated_at?->toJSON()
                : null,
        ];
    }

    public function arrivalForBooking(string $bookingId): ?array
    {
        $arrival = PanditArrival::query()->where('booking_id', $bookingId)->first();
        if ($arrival === null) {
            return null;
        }

        return [
            'id' => (string) $arrival->getKey(),
            'pandit_id' => (string) $arrival->pandit_id,
            'arrived_at' => $arrival->arrived_at->toJSON(),
            'source_channel' => $arrival->source_channel,
            'delta_minutes' => $arrival->delta_minutes,
            'corroborated_at' => $arrival->corroborated_at?->toJSON(),
        ];
    }
}
