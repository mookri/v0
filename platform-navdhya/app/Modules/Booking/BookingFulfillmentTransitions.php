<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\TransitionsBookingFulfillment;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;

/**
 * Fulfillment-outcome port implementation (B4): applies pandit-side
 * assignment facts to bookings through the BookingLifecycle matrix,
 * appending a transition row per matrix hop and an audit record per
 * leg. Runs under the ambient tenant resolved by the pandit request;
 * the row lock serializes against concurrent verbs.
 *
 * Actor is the acting pandit's user id — a human principal requested
 * these transitions (K-029 attribution; contrast the payment port's
 * system actor).
 *
 * N2: every applied outcome appends booking.reservation.transitioned
 * with the assignment fact's reason — the drain replays it into
 * customer notifications (pandit assigned/booking completed).
 */
final class BookingFulfillmentTransitions implements TransitionsBookingFulfillment
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    public function assignmentAccepted(string $bookingId, string $actorId): array
    {
        return $this->apply(
            $bookingId,
            [BookingLifecycle::STATUS_CONFIRMED],
            BookingLifecycle::STATUS_ASSIGNED,
            'booking.fulfillment.assigned',
            'assignment_accepted',
            $actorId,
        );
    }

    public function assignmentCompleted(string $bookingId, string $actorId): array
    {
        return $this->apply(
            $bookingId,
            [BookingLifecycle::STATUS_ASSIGNED],
            BookingLifecycle::STATUS_COMPLETED,
            'booking.fulfillment.completed',
            'assignment_completed',
            $actorId,
        );
    }

    /**
     * @param  list<string>  $allowedFrom
     * @return array{ok: true, from: string, to: string}|array{ok: false, reason: 'not_found'|'state_forbidden', from?: string}
     */
    private function apply(string $bookingId, array $allowedFrom, string $to, string $auditAction, string $reason, string $actorId): array
    {
        $booking = Booking::query()
            ->where('id', $bookingId)
            ->lockForUpdate()
            ->first();

        if ($booking === null) {
            return ['ok' => false, 'reason' => 'not_found'];
        }

        $from = (string) $booking->status;

        // The statuses each fulfillment fact may apply to (replay /
        // out-of-order safety): a second pandit's acceptance after the
        // booking is already ASSIGNED, a completion after cancellation —
        // all refuse without writing.
        if (! in_array($from, $allowedFrom, true)) {
            return ['ok' => false, 'reason' => 'state_forbidden', 'from' => $from];
        }

        $booking->forceFill([
            'status' => $to,
            'version' => $booking->version + 1,
        ])->save();

        if ($to === BookingLifecycle::STATUS_COMPLETED) {
            // Completion drives both matrix hops (ASSIGNED → IN_PROGRESS
            // → COMPLETED); each is recorded so the timeline matches the
            // lifecycle exactly.
            $this->record($booking, BookingLifecycle::STATUS_ASSIGNED, BookingLifecycle::STATUS_IN_PROGRESS, $reason, $actorId);
            $this->record($booking, BookingLifecycle::STATUS_IN_PROGRESS, BookingLifecycle::STATUS_COMPLETED, $reason, $actorId);
        } else {
            $this->record($booking, $from, $to, $reason, $actorId);
        }

        $this->audit->record(
            $auditAction,
            ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
            ['status' => $from],
            ['status' => $to],
        );

        // N2: the lifecycle fact rides the assignment manager's
        // transaction (same ambient tenant connection as the state
        // change — same-tx outbox guarantee).
        $this->outbox->append(
            (string) $booking->tenant_id,
            'booking',
            (string) $booking->getKey(),
            'booking.reservation.transitioned',
            [
                'booking_id' => (string) $booking->getKey(),
                'user_id' => (string) $booking->user_id,
                'from' => (string) $from,
                'to' => (string) $to,
                'reason' => $reason,
                'actor_id' => $actorId,
            ],
        );

        return ['ok' => true, 'from' => $from, 'to' => $to];
    }

    private function record(Booking $booking, string $from, string $to, string $reason, string $actorId): void
    {
        $transition = new BookingTransition();
        $transition->forceFill([
            'booking_id' => (string) $booking->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'actor_id' => $actorId,
        ]);
        $transition->save();
    }
}
