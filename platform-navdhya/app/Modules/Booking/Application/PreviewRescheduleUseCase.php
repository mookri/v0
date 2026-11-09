<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLine;
use Carbon\CarbonImmutable;

/**
 * Pure read-only preview evaluating rescheduling feasibility, slot availability,
 * and applicable rescheduling fee without applying changes.
 */
final class PreviewRescheduleUseCase
{
    /**
     * @return array{
     *     eligible: bool,
     *     current_scheduled_at: string,
     *     new_scheduled_at: string,
     *     fee_minor: int,
     *     currency: string,
     *     slot_available: bool
     * }
     */
    public function execute(
        string $tenantId,
        string $bookingId,
        int|string $userId,
        CarbonImmutable $newStart
    ): array {
        /** @var Booking|null $booking */
        $booking = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $bookingId)
            ->where('user_id', $userId)
            ->first();

        if ($booking === null) {
            throw new BookingNotFoundException('Booking not found.');
        }

        if (! in_array($booking->status, [
            BookingLifecycle::STATUS_DRAFT,
            BookingLifecycle::STATUS_PENDING_PAYMENT,
            BookingLifecycle::STATUS_PAYMENT_CONFIRMED,
            BookingLifecycle::STATUS_CONFIRMED,
        ], true)) {
            throw new BookingStateForbiddenException("Booking in status '{$booking->status}' cannot be rescheduled.");
        }

        $now = CarbonImmutable::now('UTC');
        if ($newStart->isBefore($now)) {
            throw new BookingStateForbiddenException('Cannot reschedule to a past time.');
        }

        $currentStart = CarbonImmutable::parse($booking->scheduled_at ?? $now->toDateTimeString());
        $hoursUntilCurrent = (float) $now->diffInMinutes($currentStart, false) / 60.0;

        // Cutoff: cannot reschedule within 2 hours of current start
        if ($hoursUntilCurrent < 2.0 && in_array($booking->status, [BookingLifecycle::STATUS_CONFIRMED, BookingLifecycle::STATUS_PAYMENT_CONFIRMED], true)) {
            throw new BookingStateForbiddenException('Rescheduling is not permitted within 2 hours of the scheduled start.');
        }

        $duration = (int) ($booking->duration_minutes ?? 60);
        $newEnd = $newStart->addMinutes($duration);

        // Check availability of assigned pandit / temple
        $slotAvailable = true;
        if ($booking->current_revision_id !== null) {
            $lines = BookingLine::query()
                ->where('tenant_id', $tenantId)
                ->where('revision_id', $booking->current_revision_id)
                ->get();

            foreach ($lines as $line) {
                if ($line->selected_pandit_id !== null) {
                    $hasConflict = \App\Modules\Booking\Models\ResourceAllocation::query()
                        ->where('tenant_id', $tenantId)
                        ->where('resource_type', 'PANDIT')
                        ->where('resource_id', $line->selected_pandit_id)
                        ->where('booking_id', '!=', (string) $booking->getKey())
                        ->whereIn('status', [\App\Modules\Booking\Models\ResourceAllocation::STATUS_HELD, \App\Modules\Booking\Models\ResourceAllocation::STATUS_COMMITTED])
                        ->where('start_at', '<', $newEnd->toDateTimeString())
                        ->where('end_at', '>', $newStart->toDateTimeString())
                        ->exists();

                    if ($hasConflict) {
                        $slotAvailable = false;
                        break;
                    }
                }
            }
        }

        // Fee policy: free if > 48h, 10% fee if 2h - 48h
        $totalMinor = (int) ($booking->amount_minor ?? $booking->total_minor ?? 0);
        $feeMinor = 0;
        if ($hoursUntilCurrent < 48.0 && in_array($booking->status, [BookingLifecycle::STATUS_CONFIRMED, BookingLifecycle::STATUS_PAYMENT_CONFIRMED], true)) {
            $feeMinor = (int) intdiv($totalMinor, 10);
        }

        return [
            'eligible' => true,
            'current_scheduled_at' => (string) $booking->scheduled_at,
            'new_scheduled_at' => $newStart->toISOString(),
            'fee_minor' => $feeMinor,
            'currency' => $booking->currency ?? 'INR',
            'slot_available' => $slotAvailable,
        ];
    }
}

