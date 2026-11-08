<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLine;
use App\Modules\Booking\Models\BookingTransition;
use App\Platform\Audit\AuditWriter;
use Carbon\CarbonImmutable;

/**
 * Initiates checkout by reserving resources and placing holds (Phase 2, Task 2.1.3).
 */
final class InitiateCheckoutHoldUseCase
{
    public function __construct(
        private readonly ResourceAllocationManager $allocations,
        private readonly AuditWriter $audit,
    ) {
    }

    public function execute(string $tenantId, string $bookingId, string $actorId, int $holdMinutes = 15): Booking
    {
        /** @var Booking $booking */
        $booking = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $bookingId)
            ->lockForUpdate()
            ->firstOrFail();

        if (! in_array($booking->status, [BookingLifecycle::STATUS_DRAFT, BookingLifecycle::STATUS_PENDING_PAYMENT], true)) {
            throw new BookingStateForbiddenException("Cannot checkout booking in status '{$booking->status}'.");
        }

        $now = CarbonImmutable::now();
        $expiresAt = $now->addMinutes($holdMinutes);
        $start = CarbonImmutable::parse($booking->scheduled_at ?? $now->toDateTimeString());
        $end = $start->addMinutes((int) $booking->duration_minutes);

        $booking->getConnection()->transaction(function () use ($booking, $tenantId, $actorId, $start, $end, $expiresAt): void {
            // Find current revision lines
            if ($booking->current_revision_id !== null) {
                $lines = BookingLine::query()
                    ->where('tenant_id', $tenantId)
                    ->where('revision_id', $booking->current_revision_id)
                    ->get();

                foreach ($lines as $line) {
                    if ($line->selected_pandit_id !== null) {
                        $this->allocations->holdPanditSlot(
                            $tenantId,
                            $line->selected_pandit_id,
                            (string) $booking->getKey(),
                            $booking->current_revision_id,
                            $start,
                            $end,
                            $expiresAt
                        );
                    }

                    if ($line->selected_temple_id !== null) {
                        $this->allocations->holdTempleSlot(
                            $tenantId,
                            $line->selected_temple_id,
                            (string) $booking->getKey(),
                            $booking->current_revision_id,
                            $start,
                            $end,
                            10, // Default temple slot capacity limit
                            $expiresAt
                        );
                    }
                }
            }

            $from = $booking->status;
            $booking->status = BookingLifecycle::STATUS_PENDING_PAYMENT;
            $booking->checkout_expires_at = $expiresAt;
            $booking->version += 1;
            $booking->save();

            $transition = new BookingTransition();
            $transition->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => (string) $booking->getKey(),
                'from_status' => $from,
                'to_status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
                'reason' => 'checkout_initiated',
                'actor_id' => $actorId,
            ]);
            $transition->save();

            $this->audit->record(
                'booking.checkout.initiated',
                ['module' => 'booking', 'resource_type' => 'booking', 'resource_id' => (string) $booking->getKey()],
                null,
                ['expires_at' => $expiresAt->toDateTimeString()]
            );
        });

        return $booking;
    }
}
