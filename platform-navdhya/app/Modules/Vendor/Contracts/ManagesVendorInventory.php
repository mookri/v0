<?php

namespace App\Modules\Vendor\Contracts;

/**
 * Vendor inventory write port (deptrac-blessed Contracts surface):
 * stock adjustments for a vendor's SKUs. Ambient tenant context scopes
 * every call.
 *
 * @phpstan-type VendorInventoryRow array{id: string, object: string, vendor_id: string, sku: string, on_hand: int, created_at: string, updated_at: string}
 */
interface ManagesVendorInventory
{
    /**
     * Apply a signed stock delta (row is created on first adjustment);
     * stock never goes negative.
     *
     * @return VendorInventoryRow
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the vendor is absent or out of tenant scope
     * @throws \App\Platform\Api\CodedHttpException 409 conflict when the delta would drive stock below zero
     */
    public function adjust(string $vendorId, string $sku, int $delta): array;
}
