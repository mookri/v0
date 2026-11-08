<?php

namespace App\Modules\Booking\Domain;

use App\Modules\Booking\Domain\Exceptions\ResourceUnavailableException;
use App\Modules\Booking\Models\StockReservation;
use App\Modules\Vendor\Models\VendorInventory;
use Carbon\CarbonImmutable;

/**
 * Kit Stock Reservation Engine (Phase 2, Task 2.2.4).
 * Guarantees reserved_units <= on_hand under inventory row lock.
 */
final class StockReservationManager
{
    public function holdStock(
        string $tenantId,
        string $vendorId,
        string $sku,
        string $bookingId,
        ?string $revisionId,
        int $units,
        CarbonImmutable $expiresAt
    ): StockReservation {
        /** @var VendorInventory|null $inventory */
        $inventory = VendorInventory::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->where('sku', $sku)
            ->lockForUpdate()
            ->first();

        if ($inventory === null) {
            throw new ResourceUnavailableException("Vendor inventory not found for SKU '{$sku}'.");
        }

        $now = now();
        $currentlyReserved = (int) StockReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->where('sku', $sku)
            ->where(function ($query) use ($now): void {
                $query->where('status', StockReservation::STATUS_COMMITTED)
                    ->orWhere(function ($heldQuery) use ($now): void {
                        $heldQuery->where('status', StockReservation::STATUS_HELD)
                            ->where('expires_at', '>', $now);
                    });
            })
            ->sum('units');

        if (($currentlyReserved + $units) > (int) $inventory->on_hand) {
            throw new ResourceUnavailableException("Insufficient stock for kit SKU '{$sku}'. Available: ".($inventory->on_hand - $currentlyReserved).", Requested: {$units}");
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
        $reservations = StockReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->where('status', StockReservation::STATUS_COMMITTED)
            ->lockForUpdate()
            ->get();

        $consumed = 0;
        foreach ($reservations as $res) {
            /** @var VendorInventory|null $inventory */
            $inventory = VendorInventory::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('vendor_id', $res->vendor_id)
                ->where('sku', $res->sku)
                ->lockForUpdate()
                ->first();

            if ($inventory !== null) {
                $inventory->on_hand = max(0, $inventory->on_hand - $res->units);
                $inventory->save();
            }

            $res->status = StockReservation::STATUS_CONSUMED;
            $res->save();
            $consumed++;
        }

        return $consumed;
    }

    public function releaseStock(string $tenantId, string $bookingId): int
    {
        return StockReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->whereIn('status', [StockReservation::STATUS_HELD, StockReservation::STATUS_COMMITTED])
            ->update([
                'status' => StockReservation::STATUS_RELEASED,
            ]);
    }
}

