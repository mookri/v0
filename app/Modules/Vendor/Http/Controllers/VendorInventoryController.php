<?php

namespace App\Modules\Vendor\Http\Controllers;

use App\Modules\Vendor\Contracts\ManagesVendorInventory;
use App\Modules\Vendor\Http\VendorOwnership;
use App\Modules\Vendor\Models\VendorInventory;
use App\Platform\Api\CursorPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor inventory controller: cursor list of stock rows plus the
 * signed-delta adjust verb (RPC style — rows are created on first
 * adjustment).
 */
final class VendorInventoryController
{
    use VendorOwnership;

    public function __construct(
        private readonly ManagesVendorInventory $inventory,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PARTNER: List stock rows for the vendor
    public function index(Request $request, string $id): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = VendorInventory::query()->where('vendor_id', $vendor['id']);
        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(static fn (VendorInventory $row): array => VendorInventory::shape($row), $items),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    // PARTNER: Apply a signed stock delta (RPC verb)
    public function adjust(Request $request, string $id): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:64'],
            'delta' => ['required', 'integer', 'min:-1000000', 'max:1000000'],
        ]);

        $row = $this->inventory->adjust($vendor['id'], $validated['sku'], (int) $validated['delta']);

        return new JsonResponse([
            'data' => $row,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
