<?php

namespace App\Modules\Booking\Contracts;

/**
 * Fulfillment-outcome write port (deptrac-blessed Contracts surface,
 * consumed by the Pandit module's self-serve verbs): the pandit module
 * owns the ASSIGNMENT fact, the booking module owns the STATE MACHINE —
 * assignment acceptance and completion are applied through
 * BookingLifecycle here, never by writing booking rows across module
 * lines (same posture as TransitionsBookingPayment).
 *
 * Every leg is idempotent by construction: an outcome that does not
 * apply to the booking's current status is refused with a stable reason
 * and writes nothing (replay and double-accept safety — only one
 * pandit's acceptance can ever move a booking to ASSIGNED).
 *
 * Actor is the acting pandit's user id (a human principal requested
 * this transition — K-029 attribution).
 */
interface TransitionsBookingFulfillment
{
    /**
     * Pandit accepted the assignment: CONFIRMED → ASSIGNED.
     *
     * @return array{ok: true, from: string, to: string}|array{ok: false, reason: 'not_found'|'state_forbidden', from?: string}
     */
    public function assignmentAccepted(string $bookingId, string $actorId): array;

    /**
     * Pandit began performing the ceremony: ASSIGNED → IN_PROGRESS.
     *
     * @return array{ok: true, from: string, to: string}|array{ok: false, reason: 'not_found'|'state_forbidden', from?: string}
     */
    public function assignmentStarted(string $bookingId, string $actorId): array;

    /**
     * Pandit completed the work: ASSIGNED|IN_PROGRESS → COMPLETED.
     *
     * @return array{ok: true, from: string, to: string}|array{ok: false, reason: 'not_found'|'state_forbidden', from?: string}
     */
    public function assignmentCompleted(string $bookingId, string $actorId): array;
}
