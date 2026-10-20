<?php

namespace App\Modules\Vendor\Http\Controllers;

use App\Modules\Vendor\Contracts\ManagesVendorOrders;
use App\Modules\Vendor\Http\VendorOwnership;
use App\Modules\Vendor\Models\VendorOrder;
use App\Modules\Vendor\Models\VendorOrderItem;
use App\Platform\Api\CursorPagination;
use App\Platform\Api\CodedHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor order controller: cursor list + placement and the RPC lifecycle
 * verbs (:advance, :cancel). Detail responses inline the line snapshots.
 */
final class VendorOrderController
{
    use VendorOwnership;

    public function __construct(
        private readonly ManagesVendorOrders $orders,
    ) {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PARTNER: List the vendor's orders (optional status filter)
    public function index(Request $request, string $id): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'max:24'],
        ]);

        $query = VendorOrder::query()->where('vendor_id', $vendor['id']);

        if (is_string($request->query('status')) && $request->query('status') !== '') {
            $query->where('status', $request->query('status'));
        }

        [$items, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(static fn (VendorOrder $order): array => VendorOrder::shape($order), $items),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    // PARTNER: Place an order (status NEW, lines snapshotted)
    public function store(Request $request, string $id): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:64'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.sku' => ['required', 'string', 'max:64'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_minor' => ['required', 'integer', 'min:0'],
        ]);

        $order = $this->orders->place($vendor['id'], $validated, (int) $this->identityId($request));

        return new JsonResponse([
            'data' => $order,
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    // PARTNER: Order detail with line snapshots
    public function show(Request $request, string $id, string $orderId): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $order = VendorOrder::query()
            ->where('vendor_id', $vendor['id'])
            ->where('id', $orderId)
            ->first();

        if ($order === null) {
            throw new CodedHttpException(404, 'not_found', 'Order not found.');
        }

        $data = VendorOrder::shape($order);
        $data['items'] = VendorOrderItem::query()
            ->where('order_id', $order->getKey())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(static fn (VendorOrderItem $item): array => VendorOrderItem::shape($item))
            ->all();

        return new JsonResponse([
            'data' => $data,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Advance the order one lifecycle step (RPC verb)
    public function advance(Request $request, string $id, string $orderId): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $order = $this->orders->advance($vendor['id'], $orderId, (int) $this->identityId($request));

        return new JsonResponse([
            'data' => $order,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Cancel a NEW/PROCESSING order (RPC verb)
    public function cancel(Request $request, string $id, string $orderId): JsonResponse
    {
        $vendor = $this->ownedVendor($request, $id);

        $order = $this->orders->cancel($vendor['id'], $orderId, (int) $this->identityId($request));

        return new JsonResponse([
            'data' => $order,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
