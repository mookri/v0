<?php

namespace App\Modules\Vendor;

use App\Modules\Vendor\Contracts\ManagesVendorOrders;
use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorOrder;
use App\Modules\Vendor\Models\VendorOrderItem;
use App\Modules\Vendor\Models\VendorOrderTransition;
use App\Platform\Api\CodedHttpException;

/**
 * ManagesVendorOrders port implementation: placement and the linear
 * lifecycle. Every mutation appends a transition row (from → to with the
 * acting user); orders are always resolved inside their vendor so a
 * foreign order id is indistinguishable from an absent one (404).
 */
final class VendorOrderService implements ManagesVendorOrders
{
    public function place(string $vendorId, array $data, ?int $actorUserId): array
    {
        Vendor::query()->findOrFail($vendorId);

        if (VendorOrder::query()
            ->where('vendor_id', $vendorId)
            ->where('reference', $data['reference'])
            ->exists()) {
            throw new CodedHttpException(409, 'conflict', 'Order reference already exists for this vendor.');
        }

        $lines = [];
        $total = 0;

        foreach ($data['items'] as $line) {
            $lineMinor = $line['quantity'] * $line['unit_minor'];
            $total += $lineMinor;
            $lines[] = [
                'sku' => $line['sku'],
                'name' => $line['name'],
                'quantity' => $line['quantity'],
                'unit_minor' => $line['unit_minor'],
                'line_minor' => $lineMinor,
            ];
        }

        $order = new VendorOrder();
        $order->forceFill([
            'vendor_id' => $vendorId,
            'reference' => $data['reference'],
            'customer_name' => $data['customer_name'] ?? null,
            'status' => VendorOrder::STATUS_NEW,
            'currency' => $data['currency'] ?? 'INR',
            'total_minor' => $total,
        ]);
        $order->save();

        foreach ($lines as $line) {
            $item = new VendorOrderItem();
            $item->forceFill([
                'order_id' => $order->getKey(),
                ...$line,
            ]);
            $item->save();
        }

        $this->transition($order->getKey(), null, VendorOrder::STATUS_NEW, $actorUserId, null);

        return $this->detail($order);
    }

    public function advance(string $vendorId, string $orderId, ?int $actorUserId): array
    {
        $order = VendorOrder::query()
            ->where('vendor_id', $vendorId)
            ->where('id', $orderId)
            ->firstOrFail();

        $next = match ($order->status) {
            VendorOrder::STATUS_NEW => VendorOrder::STATUS_PROCESSING,
            VendorOrder::STATUS_PROCESSING => VendorOrder::STATUS_DISPATCHED,
            VendorOrder::STATUS_DISPATCHED => VendorOrder::STATUS_DELIVERED,
            VendorOrder::STATUS_DELIVERED => throw new CodedHttpException(409, 'state_forbidden', 'Order is already delivered.'),
            default => throw new CodedHttpException(409, 'state_forbidden', 'Cancelled orders cannot advance.'),
        };

        $from = $order->status;
        $order->forceFill(['status' => $next])->save();
        $this->transition($order->getKey(), $from, $next, $actorUserId, null);

        return $this->detail($order);
    }

    public function cancel(string $vendorId, string $orderId, ?int $actorUserId): array
    {
        $order = VendorOrder::query()
            ->where('vendor_id', $vendorId)
            ->where('id', $orderId)
            ->firstOrFail();

        if (! in_array($order->status, [VendorOrder::STATUS_NEW, VendorOrder::STATUS_PROCESSING], true)) {
            throw new CodedHttpException(409, 'state_forbidden', 'Only new or processing orders can be cancelled.');
        }

        $from = $order->status;
        $order->forceFill(['status' => VendorOrder::STATUS_CANCELLED])->save();
        $this->transition($order->getKey(), $from, VendorOrder::STATUS_CANCELLED, $actorUserId, null);

        return $this->detail($order);
    }

    /**
     * @return array{id: string, object: string, vendor_id: string, reference: string, customer_name: string|null, status: string, currency: string, total_minor: int, created_at: string, updated_at: string, items: list<array{id: string, sku: string, name: string, quantity: int, unit_minor: int, line_minor: int}>}
     */
    private function detail(VendorOrder $order): array
    {
        $data = VendorOrder::shape($order);
        $data['items'] = VendorOrderItem::query()
            ->where('order_id', $order->getKey())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(static fn (VendorOrderItem $item): array => VendorOrderItem::shape($item))
            ->all();

        return $data;
    }

    private function transition(string $orderId, ?string $from, string $to, ?int $actorUserId, ?string $note): void
    {
        $row = new VendorOrderTransition();
        $row->forceFill([
            'order_id' => $orderId,
            'from_status' => $from,
            'to_status' => $to,
            'actor_user_id' => $actorUserId,
            'note' => $note,
        ]);
        $row->save();
    }
}
