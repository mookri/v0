<?php

namespace App\Modules\Vendor;

use App\Modules\Vendor\Contracts\ManagesVendorOrders;
use App\Modules\Vendor\Models\Shipment;
use App\Modules\Vendor\Models\ShipmentEvent;
use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorOrder;
use App\Modules\Vendor\Models\VendorOrderItem;
use App\Modules\Vendor\Models\VendorOrderTransition;
use App\Platform\Api\CodedHttpException;

/**
 * ManagesVendorOrders port implementation: placement, linear lifecycle,
 * and multi-stage dispatch pipeline (D5).
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

    public function advance(string $vendorId, string $orderId, ?int $actorUserId, array $payload = []): array
    {
        $order = VendorOrder::query()
            ->where('vendor_id', $vendorId)
            ->where('id', $orderId)
            ->firstOrFail();

        $requestedStatus = $payload['status'] ?? null;

        // Linear lifecycle supports both classic 4-step and granular dispatch pipeline (D5)
        $next = match ($order->status) {
            VendorOrder::STATUS_NEW => $requestedStatus ?? VendorOrder::STATUS_PROCESSING,
            VendorOrder::STATUS_PROCESSING => $requestedStatus ?? (isset($payload['carrier_name']) || isset($payload['tracking_code']) ? Shipment::STATUS_HANDOFF : VendorOrder::STATUS_DISPATCHED),
            Shipment::STATUS_PACKED => $requestedStatus ?? Shipment::STATUS_HANDOFF,
            Shipment::STATUS_HANDOFF => $requestedStatus ?? Shipment::STATUS_IN_TRANSIT,
            Shipment::STATUS_IN_TRANSIT => $requestedStatus ?? VendorOrder::STATUS_DELIVERED,
            VendorOrder::STATUS_DISPATCHED => VendorOrder::STATUS_DELIVERED,
            VendorOrder::STATUS_DELIVERED => throw new CodedHttpException(409, 'state_forbidden', 'Order is already delivered.'),
            default => throw new CodedHttpException(409, 'state_forbidden', 'Cancelled orders cannot advance.'),
        };

        // Money check guard: PACKED -> HANDOFF or PROCESSING -> HANDOFF / DISPATCHED
        if (in_array($next, [Shipment::STATUS_HANDOFF, VendorOrder::STATUS_DISPATCHED], true)) {
            // Check if order is backed by a booking
            if (str_starts_with($order->reference, 'bkg_')) {
                $bookingId = substr($order->reference, 4);
                $bookingStatus = (new VendorOrder())->getConnection()
                    ->table('bookings')
                    ->where('id', $bookingId)
                    ->value('status');

                if ($bookingStatus !== null && $bookingStatus !== 'CONFIRMED') {
                    throw new CodedHttpException(409, 'payment_not_confirmed', 'Order cannot be dispatched before payment is confirmed.');
                }
            }

            if ($next === Shipment::STATUS_HANDOFF) {
                if (empty($payload['carrier_name']) || empty($payload['tracking_code'])) {
                    throw new CodedHttpException(422, 'validation_failed', 'Carrier name and tracking code are required for handoff.');
                }
            }
        }

        if ($next === VendorOrder::STATUS_DELIVERED && in_array($order->status, [Shipment::STATUS_IN_TRANSIT, Shipment::STATUS_HANDOFF], true)) {
            if (empty($payload['pod_note'])) {
                throw new CodedHttpException(422, 'validation_failed', 'Proof of delivery note (pod_note) is required to deliver order.');
            }
        }

        // Update / create shipment record for dispatch stages
        if (in_array($next, [Shipment::STATUS_PACKED, Shipment::STATUS_HANDOFF, Shipment::STATUS_IN_TRANSIT, VendorOrder::STATUS_DELIVERED], true)) {
            $shipment = Shipment::withoutGlobalScopes()->where('order_id', $order->getKey())->first();
            if ($shipment === null) {
                $shipment = new Shipment();
                $shipment->forceFill([
                    'tenant_id' => $order->tenant_id,
                    'order_id' => $order->getKey(),
                    'status' => $next,
                ]);
            } else {
                $shipment->status = $next;
            }

            if (! empty($payload['carrier_name'])) {
                $shipment->carrier_name = (string) $payload['carrier_name'];
            }
            if (! empty($payload['tracking_code'])) {
                $shipment->tracking_code = (string) $payload['tracking_code'];
            }
            if ($next === Shipment::STATUS_HANDOFF || $next === Shipment::STATUS_IN_TRANSIT) {
                $shipment->dispatched_at = now();
            }
            if ($next === VendorOrder::STATUS_DELIVERED) {
                $shipment->delivered_at = now();
                if (! empty($payload['pod_note'])) {
                    $shipment->pod_note = (string) $payload['pod_note'];
                }
            }
            $shipment->save();

            $shipmentEvent = new ShipmentEvent();
            $shipmentEvent->forceFill([
                'tenant_id' => $order->tenant_id,
                'shipment_id' => $shipment->getKey(),
                'status' => $next,
                'note' => $payload['pod_note'] ?? $payload['note'] ?? null,
                'actor_id' => $actorUserId !== null ? (string) $actorUserId : null,
            ]);
            $shipmentEvent->save();
        }

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

    private function transition(string $orderId, ?string $from, string $to, ?int $actorUserId, ?string $reason): void
    {
        $row = new VendorOrderTransition();
        $row->forceFill([
            'order_id' => $orderId,
            'from_status' => $from,
            'to_status' => $to,
            'actor_user_id' => $actorUserId,
            'note' => $reason,
        ]);
        $row->save();
    }
}
