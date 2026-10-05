<?php

namespace App\Modules\Vendor\Contracts;

/**
 * Vendor order write port (deptrac-blessed Contracts surface): placement
 * and the linear lifecycle (NEW → PROCESSING → DISPATCHED → DELIVERED,
 * cancellable before dispatch). Ambient tenant context scopes every call;
 * every mutation appends a transition row.
 *
 * @phpstan-type VendorOrderLine array{id: string, sku: string, name: string, quantity: int, unit_minor: int, line_minor: int}
 * @phpstan-type VendorOrderDetail array{id: string, object: string, vendor_id: string, reference: string, customer_name: string|null, status: string, currency: string, total_minor: int, created_at: string, updated_at: string, items: list<VendorOrderLine>}
 */
interface ManagesVendorOrders
{
    /**
     * Place an order (status NEW) with line snapshots; total_minor is
     * derived from the lines and the first transition is recorded.
     *
     * @param  array{reference: string, customer_name?: string|null, currency?: string, items: list<array{sku: string, name: string, quantity: int, unit_minor: int}>}  $data
     * @return VendorOrderDetail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the vendor is absent or out of tenant scope
     * @throws \App\Platform\Api\CodedHttpException 409 conflict when the reference already exists for this vendor
     */
    public function place(string $vendorId, array $data, ?int $actorUserId): array;

    /**
     * Advance the order one lifecycle step.
     *
     * @return VendorOrderDetail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the order is absent, out of tenant scope, or for another vendor
     * @throws \App\Platform\Api\CodedHttpException 409 state_forbidden when the order is terminal or cancelled
     */
    public function advance(string $vendorId, string $orderId, ?int $actorUserId): array;

    /**
     * Cancel a NEW or PROCESSING order.
     *
     * @return VendorOrderDetail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the order is absent, out of tenant scope, or for another vendor
     * @throws \App\Platform\Api\CodedHttpException 409 state_forbidden when the order already left the cancellable window
     */
    public function cancel(string $vendorId, string $orderId, ?int $actorUserId): array;
}
