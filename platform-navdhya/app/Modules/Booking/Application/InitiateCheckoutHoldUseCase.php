<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Domain\Exceptions\BookingConflictException;
use App\Modules\Booking\Domain\Exceptions\BookingStateForbiddenException;
use App\Modules\Booking\Domain\ResourceAllocationManager;
use App\Modules\Booking\Domain\StockReservationManager;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLine;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Pandit\Contracts\PanditSchedulePolicy;
use App\Modules\Tax\Contracts\QuotesCommercials;
use App\Platform\Audit\AuditWriter;
use Carbon\CarbonImmutable;

/**
 * Initiates checkout by reserving resources and placing holds (Phase 2, Task 2.1.3; WP3).
 */
final class InitiateCheckoutHoldUseCase
{
    public function __construct(
        private readonly ResourceAllocationManager $allocations,
        private readonly AuditWriter $audit,
        private readonly ?PanditSchedulePolicy $schedulePolicy = null,
        private readonly ?StockReservationManager $stock = null,
        private readonly ?QuotesCommercials $quoteService = null,
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

        if (!in_array($booking->status, [BookingLifecycle::STATUS_DRAFT, BookingLifecycle::STATUS_PENDING_PAYMENT], true)) {
            throw new BookingStateForbiddenException("Cannot checkout booking in status '{$booking->status}'.");
        }

        $now = CarbonImmutable::now();

        // WP3: Quote Drift Guard - verify freshness of commercial quote if one was accepted
        if ($booking->accepted_quote_hash !== null) {
            $quoteSvc = $this->quoteService ?? app(QuotesCommercials::class);
            $quoteLines = [];
            if ($booking->current_revision_id !== null) {
                $rawLines = BookingLine::query()
                    ->where('tenant_id', $tenantId)
                    ->where('revision_id', $booking->current_revision_id)
                    ->get();
                foreach ($rawLines as $rawLine) {
                    $quoteLines[] = [
                        'sku' => (string) $rawLine->sku,
                        'quantity' => (int) $rawLine->quantity,
                        'pandit_id' => $rawLine->selected_pandit_id,
                    ];
                }
            }

            $quoteRequest = [
                'tenant_id' => $tenantId,
                'currency' => $booking->currency ?? 'INR',
                'jurisdiction' => $booking->address_snapshot['jurisdiction'] ?? null,
                'customer_state' => $booking->address_snapshot['state'] ?? null,
                'customer_postal_code' => $booking->address_snapshot['postal_code'] ?? null,
                'lines' => $quoteLines,
            ];

            $freshQuote = $quoteSvc->createQuote($quoteRequest, $now);
            if (! ($freshQuote['ok'] ?? false) || $freshQuote['quote_hash'] !== $booking->accepted_quote_hash) {
                throw new BookingConflictException('Commercial pricing or tax rate drifted.', 'quote_stale');
            }
        }

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
                        $policy = $this->schedulePolicy ?? app(PanditSchedulePolicy::class);
                        $effectiveWindow = $policy->getEffectiveWindow($tenantId, $line->selected_pandit_id, $start, $end);

                        $this->allocations->holdPanditSlot(
                            $tenantId,
                            $line->selected_pandit_id,
                            (string) $booking->getKey(),
                            $booking->current_revision_id,

                            $effectiveWindow->bufferedStart,
                            $effectiveWindow->bufferedEnd,
                            $expiresAt
                        );
                    }

                    if ($line->selected_temple_id !== null) {
                        $units = max(1, (int) ($line->quantity ?? 1));
                        $this->allocations->holdTempleSlot(
                            $tenantId,
                            $line->selected_temple_id,
                            (string) $booking->getKey(),
                            $booking->current_revision_id,
                            $start,
                            $end,
                            10, // Default temple slot capacity limit
                            $expiresAt,
                            $units
                        );
                    }

                    if (($line->line_kind === 'KIT' || $line->selected_vendor_id !== null) && $line->selected_vendor_id !== null) {
                        $stockMgr = $this->stock ?? app(StockReservationManager::class);
                        $stockMgr->holdStock(
                            $tenantId,
                            (string) $line->selected_vendor_id,
                            $line->sku,
                            (string) $booking->getKey(),
                            $booking->current_revision_id,
                            max(1, (int) ($line->quantity ?? 1)),
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
