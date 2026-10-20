<?php

namespace App\Modules\Vendor\Http\Controllers;

use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Catalog\Contracts\ManagesVendorCatalogItems;
use App\Modules\Vendor\Http\VendorOwnership;
use App\Platform\Api\CodedHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor product controller: partner-owned products are catalog items
 * with vendor_id set (P12a ports) — reads and writes delegate to the
 * Catalog module across the boundary as framework exceptions only.
 * Catalog maps duplicate SKUs to 409 conflict and archived immutability
 * to 409 state_forbidden (its own endpoints use the same codes).
 */
final class VendorProductController
{
    use VendorOwnership;

    public function __construct(
        private readonly ListsCatalogItems $catalog,
        private readonly ManagesVendorCatalogItems $items,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PARTNER: List the vendor's products
    public function index(Request $request, string $id): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $limit = min((int) $request->query('limit', 20), 100);

        $products = $this->catalog->listForVendor($vendor['id'], $limit);

        return new JsonResponse([
            'data' => $products,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Create a vendor product (starts DRAFT in the catalog)
    public function store(Request $request, string $id): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'price_minor' => ['nullable', 'integer', 'min:0'],
            'attributes' => ['nullable', 'array'],
        ]);

        try {
            $item = $this->items->createForVendor($vendor['id'], $validated);
        } catch (\InvalidArgumentException $e) {
            throw new CodedHttpException(409, 'conflict', $e->getMessage());
        }

        return new JsonResponse([
            'data' => $item,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    // PARTNER: Update a vendor product (archived items are immutable)
    public function update(Request $request, string $id, string $itemId): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'price_minor' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'attributes' => ['sometimes', 'nullable', 'array'],
        ]);

        try {
            $item = $this->items->updateForVendor($vendor['id'], $itemId, $validated);
        } catch (\InvalidArgumentException $e) {
            throw new CodedHttpException(409, 'state_forbidden', $e->getMessage());
        }

        return new JsonResponse([
            'data' => $item,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
