<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use App\Modules\Booking\Domain\StockReservationManager;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;

/**
 * Confirms a funded or zero-cost booking, committing held resources (Phase 2, Task 2.1.3).
 */
final class ConfirmBookingFulfillmentUseCase
{
    public function __construct(
        private readonly ResourceAllocationManager $allocations,
        private readonly StockReservationManager $stock,
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    public function execute(string $tenantId, string $bookingId, string $actorId, string $reason = 'payment_confirmed'): Booking
    {
        /** @var Booking $booking */
        $booking = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $bookingId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($booking->status === BookingLifecycle::STATUS_CONFIRMED) {
            return $booking; // Idempotent
        }

        if (! in_array($booking->status, [BookingLifecycle::STATUS_PENDING_PAYMENT, BookingLifecycle::STATUS_PAYMENT_CONFIRMED], true)) {
            throw new BookingStateForbiddenException("Cannot confirm booking from status '{$booking->status}'.");
        }

        $booking->getConnection()->transaction(function () use ($booking, $tenantId, $actorId, $reason): void {
            // Commit all held allocations & stock
            $this->allocations->commitAllocations($tenantId, (string) $booking->getKey());
            $this->stock->commitStock($tenantId, (string) $booking->getKey());

            $from = $booking->status;
            $booking->status = BookingLifecycle::STATUS_CONFIRMED;
            $booking->checkout_expires_at = null;
            $booking->version += 1;
            $booking->save();

            $transition = new BookingTransition();
            $transition->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => (string) $booking->getKey(),
                'from_status' => $from,
                'to_status' => BookingLifecycle::STATUS_CONFIRMED,
                'reason' => $reason,
                'actor_id' => $actorId,
            ]);
            $transition->save();

            $this->audit->record(
                'booking.reservation.confirmed',
                ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
                null,
                ['confirmed_by' => $actorId, 'reason' => $reason]
            );

            $this->outbox->append(
                $tenantId,
                'booking',
                (string) $booking->getKey(),
                'booking.reservation.confirmed',
                [
                    'booking_id' => (string) $booking->getKey(),
                    'user_id' => $booking->user_id,
                    'service_sku' => $booking->service_sku,
                    'revision_id' => $booking->current_revision_id,
                ]
            );
        });

        return $booking;
    }
}
