<?php

namespace App\Modules\Vendor\Contracts;

use Carbon\CarbonImmutable;

/**
 * Authoritative Stock Truth & Reservation Engine Port (D2, D3).
 * Decouples consumer modules (such as Booking) from Vendor internal tables.
 */
interface ManagesStockReservations
{
    /**
     * Reserve/hold stock for a kit SKU (exploding its BOM components) or single SKU.
     *
     * @param string $tenantId
     * @param string $vendorId
     * @param string $sku
     * @param int $quantity
     * @param string $bookingId
     * @param CarbonImmutable $expiresAt
     * @param string|null $warehouseId Explicit warehouse or null for deterministic auto-routing
     * @return StockHoldVerdict
     */
    public function holdKit(
        string $tenantId,
        string $vendorId,
        string $sku,
        int $quantity,
        string $bookingId,
        CarbonImmutable $expiresAt,
        ?string $warehouseId = null
    ): StockHoldVerdict;

    /**
     * Commit active holds for a booking.
     */
    public function commitHold(string $tenantId, string $bookingId): void;

    /**
     * Release active holds for a booking back to available inventory.
     */
    public function releaseHold(string $tenantId, string $bookingId, string $reason): void;

    /**
     * Consume committed stock (decrements on_hand and reserved counters, logs CONSUME movement).
     */
    public function consumeStock(string $tenantId, string $bookingId): void;

    /**
     * Internal stock transfer between warehouses with deadlock-free lock acquisition (D3).
     */
    public function transferStock(
        string $tenantId,
        string $vendorId,
        string $sku,
        string $fromWarehouseId,
        string $toWarehouseId,
        int $quantity,
        ?string $actorId = null
    ): void;
}
