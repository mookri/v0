<?php

namespace App\Modules\Vendor;

use App\Modules\Vendor\Contracts\ManagesVendorInventory;
use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorInventory;
use App\Platform\Api\CodedHttpException;

/**
 * ManagesVendorInventory port implementation: signed stock deltas scoped
 * to the vendor. Rows are created on first adjustment; stock never goes
 * negative (409 conflict).
 */
final class VendorInventoryService implements ManagesVendorInventory
{
    public function adjust(string $vendorId, string $sku, int $delta): array
    {
        // Scoped existence check: absent/out-of-scope vendors 404.
        Vendor::query()->findOrFail($vendorId);

        $row = VendorInventory::query()
            ->where('vendor_id', $vendorId)
            ->where('sku', $sku)
            ->first();

        if ($row === null) {
            if ($delta < 0) {
                throw new CodedHttpException(409, 'conflict', 'Stock cannot go negative.');
            }

            $row = new VendorInventory();
            $row->forceFill([
                'vendor_id' => $vendorId,
                'sku' => $sku,
                'on_hand' => $delta,
            ]);
            $row->save();

            return VendorInventory::shape($row);
        }

        $next = (int) $row->on_hand + $delta;

        if ($next < 0) {
            throw new CodedHttpException(409, 'conflict', 'Stock cannot go negative.');
        }

        $row->forceFill(['on_hand' => $next])->save();

        return VendorInventory::shape($row);
    }
}
