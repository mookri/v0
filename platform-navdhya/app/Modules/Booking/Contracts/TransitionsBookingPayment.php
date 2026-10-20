<?php

namespace App\Modules\Booking\Contracts;

/**
 * Payment-outcome write port (deptrac-blessed Contracts surface,
 * consumed by the Payment module's webhook path): the payment module
 * owns the money FACT, the booking module owns the STATE MACHINE —
 * outcomes are applied through BookingLifecycle here, never by writing
 * booking rows across module lines.
 *
 * Every leg is idempotent by construction: an outcome that does not
 * apply to the booking's current status is refused with a stable reason
 * and writes nothing (replay and out-of-order event safety).
 */
interface TransitionsBookingPayment
{
    /**
     * Capture landed: PENDING_PAYMENT → PAYMENT_CONFIRMED → CONFIRMED
     * (both matrix hops, one fact).
     *
     * @return array{ok: true, from: string, to: string}|array{ok: false, reason: 'not_found'|'state_forbidden', from?: string}
     */
    public function paymentConfirmed(string $bookingId): array;

    /**
     * Payment failed: PENDING_PAYMENT → FAILED.
     *
     * @return array{ok: true, from: string, to: string}|array{ok: false, reason: 'not_found'|'state_forbidden', from?: string}
     */
    public function paymentFailed(string $bookingId): array;

    /**
     * Refund processed: PAYMENT_CONFIRMED|CONFIRMED → REFUNDED.
     *
     * @return array{ok: true, from: string, to: string}|array{ok: false, reason: 'not_found'|'state_forbidden', from?: string}
     */
    public function paymentRefunded(string $bookingId): array;
}
