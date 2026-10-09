<?php

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\TransitionsBookingPayment;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Platform\Audit\AuditWriter;

/**
 * Payment-outcome port implementation (B3): applies webhook-driven money
 * outcomes to bookings through the BookingLifecycle matrix, appending a
 * transition row per matrix hop and an audit record per leg. Runs under
 * the ambient tenant resolved by the payment webhook; the row lock
 * serializes against concurrent verbs.
 *
 * Actor is the system: the customer did not request these transitions —
 * the payment provider reported them (K-029 attribution).
 */
final class BookingPaymentTransitions implements TransitionsBookingPayment
{
    private const SYSTEM_ACTOR = 'system:payment-webhook';

    public function __construct(
        private readonly AuditWriter $audit,
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
            // Capture drives both matrix hops (PENDING_PAYMENT →
            // PAYMENT_CONFIRMED → CONFIRMED); each is recorded so the
            // timeline matches the lifecycle exactly.
            $this->record($booking, BookingLifecycle::STATUS_PENDING_PAYMENT, BookingLifecycle::STATUS_PAYMENT_CONFIRMED, $reason);
            $this->record($booking, BookingLifecycle::STATUS_PAYMENT_CONFIRMED, BookingLifecycle::STATUS_CONFIRMED, $reason);
        } else {
            $this->record($booking, $from, $to, $reason);
        }

        $this->audit->record(
            $auditAction,
            ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
            ['status' => $from],
            ['status' => $to],
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
