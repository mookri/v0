<?php

namespace App\Modules\Booking\Http;

use App\Modules\Booking\Models\Booking;

/**
 * Presenter for canonical Booking V2 wire shapes and ETag generation.
 */
final class ReservationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Booking $booking): array
    {
        return [
            'id' => (string) $booking->getKey(),
            'version' => (int) $booking->version,
            'status' => $booking->status,
            'service_sku' => $booking->service_sku,
            'scheduled_at' => $booking->scheduled_at,
            'duration_minutes' => (int) ($booking->duration_minutes ?? 60),
            'service_start_at' => $booking->service_start_at,
            'service_end_at' => $booking->service_end_at,
            'address_snapshot' => $booking->address_snapshot,
            'amount_minor' => (int) ($booking->total_minor ?? 0),
            'currency' => $booking->currency ?? 'INR',
            'checkout_expires_at' => $booking->checkout_expires_at,
            'created_at' => $booking->created_at?->toISOString(),
            'updated_at' => $booking->updated_at?->toISOString(),
        ];
    }

    public static function etag(Booking $booking): string
    {
        return 'W/"v' . ((int) $booking->version) . '"';
    }
}

