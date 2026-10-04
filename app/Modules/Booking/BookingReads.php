<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingReview;

/**
 * ReadsBookings port implementation: booking windows (schedule overlap
 * arithmetic lives in the consumer) plus per-booking reviews, ambient-
 * tenant scoped like every other booking query.
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
}
