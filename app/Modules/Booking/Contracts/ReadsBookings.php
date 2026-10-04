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
}
