<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingChange;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Booking\Models\ResourceAllocation;
use App\Modules\Booking\Models\StockReservation;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;

final class CancelBookingUseCase
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    /**
     * Cancels an eligible booking, releases allocations/inventory, and calculates cancellation fee/refund.
     *
     * @return array{booking: Booking, change: ?BookingChange, fee_minor: int, refund_minor: int}
     */
    public function execute(
        string $tenantId,
        string $bookingId,
        int|string $userId,
        ?string $reason = null
    ): array {
        /** @var Booking $booking */
        $booking = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $bookingId)
            ->where('user_id', $userId)
            ->first();

        if ($booking === null) {
            throw new BookingNotFoundException('Booking not found.');
        }

        return $booking->getConnection()->transaction(function () use (
            $booking,
            $tenantId,
            $bookingId,
            $userId,
            $reason
        ): array {
            /** @var Booking|null $locked */
            $locked = Booking::query()
                ->whereKey((string) $booking->getKey())
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                throw new BookingNotFoundException('Booking not found.');
            }

            // Idempotent cancellation: already cancelled
            if ($locked->status === BookingLifecycle::STATUS_CANCELLED) {
                return [
                    'booking' => $locked,
                    'change' => null,
                    'fee_minor' => 0,
                    'refund_minor' => 0,
                ];
            }

            if (! BookingLifecycle::canCancel($locked->status)) {
                throw new BookingStateForbiddenException('Cannot cancel in this state.');
            }

            $fromStatus = $locked->status;
            $totalMinor = (int) ($locked->total_minor ?? 0);

            // Calculate cancellation policy fee and refund based on lead time:
            // > 72h: 0 fee (100% refund)
            // 48h - 72h: 50% fee (50% refund)
            // < 48h: 100% fee (0% refund)
            $feeMinor = 0;
            $refundMinor = $totalMinor;

            if ($locked->scheduled_at !== null) {
                $start = CarbonImmutable::parse($locked->scheduled_at);
                $hoursUntil = CarbonImmutable::now('UTC')->diffInHours($start, false);

                if ($hoursUntil < 48) {
                    $feeMinor = $totalMinor;
                    $refundMinor = 0;
                } elseif ($hoursUntil < 72) {
                    $feeMinor = (int) intdiv($totalMinor, 2);
                    $refundMinor = $totalMinor - $feeMinor;
                } else {
                    $feeMinor = 0;
                    $refundMinor = $totalMinor;
                }
            }

            // Release Resource Allocations
            ResourceAllocation::query()
                ->where('tenant_id', $tenantId)
                ->where('booking_id', $bookingId)
                ->whereIn('status', [ResourceAllocation::STATUS_HELD, ResourceAllocation::STATUS_COMMITTED])
                ->update([
                    'status' => ResourceAllocation::STATUS_RELEASED,
                    'release_reason' => 'booking_cancelled',
                ]);

            // Release Stock Reservations
            StockReservation::query()
                ->where('tenant_id', $tenantId)
                ->where('booking_id', $bookingId)
                ->whereIn('status', [StockReservation::STATUS_HELD, StockReservation::STATUS_COMMITTED])
                ->update(['status' => StockReservation::STATUS_RELEASED]);

            // Update Booking
            $locked->status = BookingLifecycle::STATUS_CANCELLED;
            $locked->version = (int) $locked->version + 1;
            $locked->save();

            // Record Booking Change
            $change = new BookingChange();
            $change->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'kind' => BookingChange::KIND_CANCELLATION,
                'fee_minor' => $feeMinor,
                'refund_minor' => $refundMinor,
                'requested_by' => (string) $userId,
                'status' => BookingChange::STATUS_APPLIED,
            ]);
            $change->save();

            // Record transition
            $transition = new BookingTransition();
            $transition->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'from_status' => $fromStatus,
                'to_status' => BookingLifecycle::STATUS_CANCELLED,
                'reason' => $reason ?? 'cancelled',
                'actor_id' => (string) $userId,
            ]);
            $transition->save();

            // Audit
            $this->audit->record(
                'booking.reservation.cancelled',
                ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => $bookingId],
                ['status' => $fromStatus],
                [
                    'user_id' => (string) $userId,
                    'status' => BookingLifecycle::STATUS_CANCELLED,
                    'reason' => $reason ?? 'cancelled',
                    'fee_minor' => $feeMinor,
                    'refund_minor' => $refundMinor,
                ]
            );

            // Outbox
            $this->outbox->append(
                $tenantId,
                'booking',
                $bookingId,
                'booking.reservation.cancelled',
                [
                    'booking_id' => $bookingId,
                    'user_id' => (string) $userId,
                    'from' => $fromStatus,
                    'to' => BookingLifecycle::STATUS_CANCELLED,
                    'fee_minor' => $feeMinor,
                    'refund_minor' => $refundMinor,
                ]
            );

            return [
                'booking' => $locked,
                'change' => $change,
                'fee_minor' => $feeMinor,
                'refund_minor' => $refundMinor,
            ];
        });
    }
}
