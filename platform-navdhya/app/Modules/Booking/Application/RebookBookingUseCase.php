<?php

namespace App\Modules\Booking\Application;

use App\Modules\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingLine;
use Carbon\CarbonImmutable;

/**
 * Creates a fresh draft reservation reusing preferences (SKU, duration, pandit, temple, address)
 * from a prior booking at a new scheduled time.
 */
final class RebookBookingUseCase
{
    public function __construct(
        private readonly CreateReservationDraftUseCase $createDraft,
    ) {
    }

    /**
     * @param array{
     *     scheduled_at: string,
     *     duration_minutes?: int|null,
     * } $overrides
     */
    public function execute(
        string $tenantId,
        string $sourceBookingId,
        int|string $userId,
        string $idempotencyKey,
        array $overrides
    ): Booking {
        /** @var Booking|null $source */
        $source = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $sourceBookingId)
            ->where('user_id', $userId)
            ->first();

        if ($source === null) {
            throw new BookingNotFoundException('Source booking not found.');
        }

        $selectedPanditId = null;
        $selectedTempleId = null;

        if ($source->current_revision_id !== null) {
            /** @var BookingLine|null $line */
            $line = BookingLine::query()
                ->where('tenant_id', $tenantId)
                ->where('revision_id', $source->current_revision_id)
                ->first();

            if ($line !== null) {
                $selectedPanditId = $line->selected_pandit_id;
                $selectedTempleId = $line->selected_temple_id;
            }
        }

        $input = [
            'service_sku' => (string) $source->service_sku,
            'scheduled_at' => (string) $overrides['scheduled_at'],
            'duration_minutes' => $overrides['duration_minutes'] ?? $source->duration_minutes ?? 60,
            'address_snapshot' => $source->address_snapshot,
            'selected_pandit_id' => $selectedPanditId,
            'selected_temple_id' => $selectedTempleId,
        ];

        return $this->createDraft->execute(
            $tenantId,
            (string) $userId,
            $idempotencyKey,
            $input
        );
    }
}

