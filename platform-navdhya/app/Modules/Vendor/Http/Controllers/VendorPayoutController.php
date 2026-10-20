<?php

namespace App\Modules\Vendor\Http\Controllers;

use App\Modules\Vendor\Http\VendorOwnership;
use App\Modules\Vendor\Models\VendorPayout;
use App\Platform\Api\CursorPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor payout controller: read-only stub — cursor list of payout rows
 * for the vendor (recording flows land later; vendor:payout:view is the
 * admin-side capability for the same data).
 */
final class VendorPayoutController
{
    use VendorOwnership;

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PARTNER: List payouts for the vendor
    public function index(Request $request, string $id): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'max:24'],
        ]);

        $query = VendorPayout::query()->where('vendor_id', $vendor['id']);

        if (is_string($request->query('status')) && $request->query('status') !== '') {
            $query->where('status', $request->query('status'));
        }

        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(static fn (VendorPayout $payout): array => VendorPayout::shape($payout), $items),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }
}
