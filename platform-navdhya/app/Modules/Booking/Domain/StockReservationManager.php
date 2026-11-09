<?php

namespace App\Modules\Booking\Domain;

use App\Modules\Booking\Domain\Exceptions\ResourceUnavailableException;
use App\Modules\Booking\Models\StockReservation;
use App\Modules\Vendor\Contracts\ManagesStockReservations;
use Carbon\CarbonImmutable;

/**
 * Kit Stock Reservation Engine (Phase 2, Task 2.2.4; Decoupled in D2).
 * Delegates authoritative stock holds, commits, and releases to ManagesStockReservations port.
 * Booking records local StockReservation mirrors for customer inspection without touching Vendor internals.
 */
final class StockReservationManager
{
    public function __construct(
        private readonly ?ManagesStockReservations $vendorStock = null,
    ) {
    }

    private function getVendorPort(): ManagesStockReservations
    {
        return $this->vendorStock ?? app(ManagesStockReservations::class);
    }

    public function holdStock(
        string $tenantId,
        string $vendorId,
        string $sku,
        string $bookingId,
        ?string $revisionId,
        int $units,
        CarbonImmutable $expiresAt
    ): StockReservation {
        $verdict = $this->getVendorPort()->holdKit(
            $tenantId,
            $vendorId,
            $sku,
            $units,
            $bookingId,
            $expiresAt
        );

        if (! $verdict->isSuccess()) {
            throw new ResourceUnavailableException($verdict->failureReason() ?? "Insufficient stock for kit SKU '{$sku}'.");
        }

        $reservation = new StockReservation();
        $reservation->forceFill([
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'sku' => $sku,
            'booking_id' => $bookingId,
            'revision_id' => $revisionId,
            'units' => $units,
            'status' => StockReservation::STATUS_HELD,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
        $reservation->save();

        return $reservation;
    }

    public function commitStock(string $tenantId, string $bookingId): int
    {
        $this->getVendorPort()->commitHold($tenantId, $bookingId);

        return StockReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->where('status', StockReservation::STATUS_HELD)
            ->update([
                'status' => StockReservation::STATUS_COMMITTED,
                'expires_at' => null,
            ]);
    }

    public function consumeStock(string $tenantId, string $bookingId): int
    {
        $this->getVendorPort()->consumeStock($tenantId, $bookingId);

        return StockReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->where('status', StockReservation::STATUS_COMMITTED)
            ->update([
                'status' => StockReservation::STATUS_CONSUMED,
            ]);
    }

    public function releaseStock(string $tenantId, string $bookingId): int
    {
        $this->getVendorPort()->releaseHold($tenantId, $bookingId, 'booking_cancelled_or_expired');

        return StockReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->whereIn('status', [StockReservation::STATUS_HELD, StockReservation::STATUS_COMMITTED])
            ->update([
                'status' => StockReservation::STATUS_RELEASED,
            ]);
    }
}
