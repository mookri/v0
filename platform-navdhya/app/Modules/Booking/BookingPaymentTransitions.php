<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\TransitionsBookingPayment;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;

/**
 * Payment-outcome port implementation (B3): applies webhook-driven money
 * outcomes to bookings through the BookingLifecycle matrix, appending a
 * transition row per matrix hop and an audit record per leg. Runs under
 * the ambient tenant resolved by the payment webhook; the row lock
 * serializes against concurrent verbs.
 *
 * Actor is the system: the customer did not request these transitions —
 * the payment provider reported them (K-029 attribution).
 *
 * N2: every applied outcome appends booking.reservation.transitioned
 * with the money fact's reason — the drain replays it into customer
 * notifications (payment received/failed/refunded).
 */
final class BookingPaymentTransitions implements TransitionsBookingPayment
{
    private const SYSTEM_ACTOR = 'system:payment-webhook';

    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
        private readonly \App\Modules\Booking\Domain\ResourceAllocationManager $allocations,
        private readonly \App\Modules\Booking\Domain\StockReservationManager $stock,
    ) {
    }

    public function paymentConfirmed(string $bookingId): array
    {
        return $this->apply($bookingId, BookingLifecycle::STATUS_CONFIRMED, 'booking.payment.confirmed', 'payment_captured');
    }

    public function paymentFailed(string $bookingId): array
    {
        return $this->apply($bookingId, BookingLifecycle::STATUS_FAILED, 'booking.payment.failed', 'payment_failed');
    }

    public function paymentRefunded(string $bookingId): array
    {
        return $this->apply($bookingId, BookingLifecycle::STATUS_REFUNDED, 'booking.payment.refunded', 'refund_processed');
    }

    /**
     * @return array{ok: true, from: string, to: string}|array{ok: false, reason: 'not_found'|'state_forbidden', from?: string}
     */
    private function apply(string $bookingId, string $to, string $auditAction, string $reason): array
    {
        $booking = Booking::query()
            ->where('id', $bookingId)
            ->lockForUpdate()
            ->first();

        if ($booking === null) {
            return ['ok' => false, 'reason' => 'not_found'];
        }

        $from = (string) $booking->status;

        // The statuses each money fact may apply to (replay / out-of-order
        // safety): a refund arriving before its capture, a capture after a
        // cancellation, a duplicate event — all refuse without writing.
        $allowedFrom = match ($to) {
            BookingLifecycle::STATUS_CONFIRMED => [BookingLifecycle::STATUS_PENDING_PAYMENT],
            BookingLifecycle::STATUS_FAILED => [BookingLifecycle::STATUS_PENDING_PAYMENT],
            BookingLifecycle::STATUS_REFUNDED => [BookingLifecycle::STATUS_PAYMENT_CONFIRMED, BookingLifecycle::STATUS_CONFIRMED],
            default => [],
        };

        if (! in_array($from, $allowedFrom, true)) {
            return ['ok' => false, 'reason' => 'state_forbidden', 'from' => $from];
        }

        $booking->forceFill([
            'status' => $to,
            'version' => $booking->version + 1,
        ])->save();

        if ($to === BookingLifecycle::STATUS_CONFIRMED) {
            $this->allocations->commitAllocations((string) $booking->tenant_id, (string) $booking->getKey());
            $this->stock->commitStock((string) $booking->tenant_id, (string) $booking->getKey());

            // Capture drives both matrix hops (PENDING_PAYMENT →
            // PAYMENT_CONFIRMED → CONFIRMED); each is recorded so the
            // timeline matches the lifecycle exactly.
            $this->record($booking, BookingLifecycle::STATUS_PENDING_PAYMENT, BookingLifecycle::STATUS_PAYMENT_CONFIRMED, $reason);
            $this->record($booking, BookingLifecycle::STATUS_PAYMENT_CONFIRMED, BookingLifecycle::STATUS_CONFIRMED, $reason);
        } elseif ($to === BookingLifecycle::STATUS_FAILED) {
            $this->allocations->releaseAllocations((string) $booking->tenant_id, (string) $booking->getKey(), 'payment_failed');
            $this->stock->releaseStock((string) $booking->tenant_id, (string) $booking->getKey());
            $this->record($booking, $from, $to, $reason);
        } else {
            $this->record($booking, $from, $to, $reason);
        }

        $this->audit->record(
            $auditAction,
            ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
            ['status' => $from],
            ['status' => $to],
        );

        // N2: the lifecycle fact rides the webhook leg's transaction
        // (same ambient tenant connection as the state change).
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
                'actor_id' => self::SYSTEM_ACTOR,
            ],
        );

        return ['ok' => true, 'from' => $from, 'to' => $to];
    }

    private function record(Booking $booking, string $from, string $to, string $reason): void
    {
        $transition = new BookingTransition();
        $transition->forceFill([
            'booking_id' => (string) $booking->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'actor_id' => self::SYSTEM_ACTOR,
        ]);
        $transition->save();
    }
}
