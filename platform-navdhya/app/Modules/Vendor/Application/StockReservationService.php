<?php

namespace App\Modules\Vendor\Application;

use App\Modules\Vendor\Contracts\ManagesStockReservations;
use App\Modules\Vendor\Contracts\StockHoldVerdict;
use App\Modules\Vendor\Models\InventoryMovement;
use App\Modules\Vendor\Models\VendorInventory;
use App\Modules\Vendor\Models\VendorKitComponent;
use App\Modules\Vendor\Models\VendorWarehouse;
use App\Platform\Api\CodedHttpException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Authoritative stock truth engine & multi-warehouse routing service (D2, D3).
 */
final class StockReservationService implements ManagesStockReservations
{
    public function holdKit(
        string $tenantId,
        string $vendorId,
        string $sku,
        int $quantity,
        string $bookingId,
        CarbonImmutable $expiresAt,
        ?string $warehouseId = null
    ): StockHoldVerdict {
        if ($quantity <= 0) {
            return StockHoldVerdict::failure("Quantity must be greater than zero.");
        }

        // 1. Kit explosion (Bill of Materials)
        $bomComponents = VendorKitComponent::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->where('kit_sku', $sku)
            ->get();

        /** @var list<array{sku: string, quantity: int}> $neededItems */
        $neededItems = [];
        if ($bomComponents->isNotEmpty()) {
            foreach ($bomComponents as $comp) {
                $neededItems[] = [
                    'sku' => $comp->component_sku,
                    'quantity' => (int) $comp->quantity * $quantity,
                ];
            }
        } else {
            $neededItems[] = [
                'sku' => $sku,
                'quantity' => $quantity,
            ];
        }

        // Sort items by SKU to prevent deadlocks when locking multiple rows
        usort($neededItems, fn ($a, $b) => strcmp($a['sku'], $b['sku']));

        // 2. Resolve Candidate Warehouses (D3 routing policy)
        $candidateWarehouses = $this->resolveCandidateWarehouses($tenantId, $vendorId, $warehouseId);

        // 3. Find a warehouse that can fulfill all BOM components
        return (new VendorInventory())->getConnection()->transaction(function () use ($tenantId, $vendorId, $sku, $neededItems, $candidateWarehouses, $bookingId) {
            foreach ($candidateWarehouses as $wh) {
                $whId = $wh?->getKey();
                $allocated = [];
                $canFulfillAll = true;

                // Acquire locks in sorted SKU order
                $inventories = [];
                foreach ($neededItems as $item) {
                    $query = VendorInventory::withoutGlobalScopes()
                        ->where('tenant_id', $tenantId)
                        ->where('vendor_id', $vendorId)
                        ->where('sku', $item['sku'])
                        ->lockForUpdate();

                    if ($whId !== null) {
                        $query->where('location_id', $whId);
                    } else {
                        $query->whereNull('location_id');
                    }

                    /** @var VendorInventory|null $inv */
                    $inv = $query->first();

                    if ($inv === null || $inv->available() < $item['quantity']) {
                        $canFulfillAll = false;
                        break;
                    }

                    $inventories[] = ['inv' => $inv, 'needed' => $item['quantity']];
                }

                if (! $canFulfillAll) {
                    continue;
                }

                // All components available at this warehouse: place holds atomically
                foreach ($inventories as $entry) {
                    /** @var VendorInventory $inv */
                    $inv = $entry['inv'];
                    $qty = $entry['needed'];

                    $inv->reserved += $qty;
                    $inv->version += 1;
                    $inv->save();

                    $movement = new InventoryMovement();
                    $movement->forceFill([
                        'tenant_id' => $tenantId,
                        'sku' => $inv->sku,
                        'location_id' => $inv->location_id,
                        'movement_type' => InventoryMovement::TYPE_HOLD,
                        'delta_signed' => -$qty,
                        'balance_after' => $inv->available(),
                        'reference_type' => 'booking',
                        'reference_id' => $bookingId,
                    ]);
                    $movement->save();

                    $allocated[] = [
                        'component_sku' => $inv->sku,
                        'quantity' => $qty,
                        'warehouse_id' => (string) ($inv->location_id ?? 'default'),
                    ];
                }

                return StockHoldVerdict::success((string) ($whId ?? 'default'), $allocated);
            }

            return StockHoldVerdict::failure("Insufficient stock for kit SKU '{$sku}' across active warehouses.");
        });
    }

    public function commitHold(string $tenantId, string $bookingId): void
    {
        (new VendorInventory())->getConnection()->transaction(function () use ($tenantId, $bookingId) {
            $holds = InventoryMovement::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('reference_type', 'booking')
                ->where('reference_id', $bookingId)
                ->where('movement_type', InventoryMovement::TYPE_HOLD)
                ->get();

            if ($holds->isEmpty()) {
                return;
            }

            foreach ($holds as $hold) {
                $commit = new InventoryMovement();
                $commit->forceFill([
                    'tenant_id' => $tenantId,
                    'sku' => $hold->sku,
                    'location_id' => $hold->location_id,
                    'movement_type' => InventoryMovement::TYPE_COMMIT,
                    'delta_signed' => 0,
                    'balance_after' => $hold->balance_after,
                    'reference_type' => 'booking',
                    'reference_id' => $bookingId,
                ]);
                $commit->save();
            }
        });
    }

    public function releaseHold(string $tenantId, string $bookingId, string $reason): void
    {
        (new VendorInventory())->getConnection()->transaction(function () use ($tenantId, $bookingId, $reason) {
            $alreadyReleased = InventoryMovement::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('reference_type', 'booking')
                ->where('reference_id', $bookingId)
                ->where('movement_type', InventoryMovement::TYPE_RELEASE)
                ->exists();

            if ($alreadyReleased) {
                return;
            }

            $holds = InventoryMovement::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('reference_type', 'booking')
                ->where('reference_id', $bookingId)
                ->where('movement_type', InventoryMovement::TYPE_HOLD)
                ->get();

            // Group holds by sku and location_id
            $grouped = [];
            foreach ($holds as $hold) {
                $key = "{$hold->sku}::" . ($hold->location_id ?? 'null');
                $grouped[$key] = [
                    'sku' => $hold->sku,
                    'location_id' => $hold->location_id,
                    'quantity' => ($grouped[$key]['quantity'] ?? 0) + abs((int) $hold->delta_signed),
                ];
            }

            foreach ($grouped as $entry) {
                $query = VendorInventory::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('sku', $entry['sku'])
                    ->lockForUpdate();

                if ($entry['location_id'] !== null) {
                    $query->where('location_id', $entry['location_id']);
                } else {
                    $query->whereNull('location_id');
                }

                $inv = $query->first();
                if ($inv !== null) {
                    $inv->reserved = max(0, $inv->reserved - $entry['quantity']);
                    $inv->version += 1;
                    $inv->save();

                    $movement = new InventoryMovement();
                    $movement->forceFill([
                        'tenant_id' => $tenantId,
                        'sku' => $inv->sku,
                        'location_id' => $inv->location_id,
                        'movement_type' => InventoryMovement::TYPE_RELEASE,
                        'delta_signed' => $entry['quantity'],
                        'balance_after' => $inv->available(),
                        'reference_type' => 'booking',
                        'reference_id' => $bookingId,
                        'actor_id' => $reason,
                    ]);
                    $movement->save();
                }
            }
        });
    }

    public function consumeStock(string $tenantId, string $bookingId): void
    {
        (new VendorInventory())->getConnection()->transaction(function () use ($tenantId, $bookingId) {
            $alreadyConsumed = InventoryMovement::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('reference_type', 'booking')
                ->where('reference_id', $bookingId)
                ->where('movement_type', InventoryMovement::TYPE_CONSUME)
                ->exists();

            if ($alreadyConsumed) {
                return;
            }

            $holds = InventoryMovement::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('reference_type', 'booking')
                ->where('reference_id', $bookingId)
                ->where('movement_type', InventoryMovement::TYPE_HOLD)
                ->get();

            $grouped = [];
            foreach ($holds as $hold) {
                $key = "{$hold->sku}::" . ($hold->location_id ?? 'null');
                $grouped[$key] = [
                    'sku' => $hold->sku,
                    'location_id' => $hold->location_id,
                    'quantity' => ($grouped[$key]['quantity'] ?? 0) + abs((int) $hold->delta_signed),
                ];
            }

            foreach ($grouped as $entry) {
                $query = VendorInventory::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('sku', $entry['sku'])
                    ->lockForUpdate();

                if ($entry['location_id'] !== null) {
                    $query->where('location_id', $entry['location_id']);
                } else {
                    $query->whereNull('location_id');
                }

                $inv = $query->first();
                if ($inv !== null) {
                    $inv->on_hand = max(0, $inv->on_hand - $entry['quantity']);
                    $inv->reserved = max(0, $inv->reserved - $entry['quantity']);
                    $inv->version += 1;
                    $inv->save();

                    $movement = new InventoryMovement();
                    $movement->forceFill([
                        'tenant_id' => $tenantId,
                        'sku' => $inv->sku,
                        'location_id' => $inv->location_id,
                        'movement_type' => InventoryMovement::TYPE_CONSUME,
                        'delta_signed' => -$entry['quantity'],
                        'balance_after' => $inv->available(),
                        'reference_type' => 'booking',
                        'reference_id' => $bookingId,
                    ]);
                    $movement->save();
                }
            }
        });
    }

    public function transferStock(
        string $tenantId,
        string $vendorId,
        string $sku,
        string $fromWarehouseId,
        string $toWarehouseId,
        int $quantity,
        ?string $actorId = null
    ): void {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new CodedHttpException(400, 'invalid_argument', 'Source and target warehouse must be distinct.');
        }

        if ($quantity <= 0) {
            throw new CodedHttpException(400, 'invalid_argument', 'Transfer quantity must be greater than zero.');
        }

        // Deadlock prevention: sort warehouse IDs lexicographically
        $firstWhId = strcmp($fromWarehouseId, $toWarehouseId) < 0 ? $fromWarehouseId : $toWarehouseId;
        $secondWhId = $firstWhId === $fromWarehouseId ? $toWarehouseId : $fromWarehouseId;

        (new VendorInventory())->getConnection()->transaction(function () use (
            $tenantId,
            $vendorId,
            $sku,
            $fromWarehouseId,
            $toWarehouseId,
            $firstWhId,
            $secondWhId,
            $quantity,
            $actorId
        ) {
            // Lock in ascending order
            $invFirst = VendorInventory::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('vendor_id', $vendorId)
                ->where('sku', $sku)
                ->where('location_id', $firstWhId)
                ->lockForUpdate()
                ->first();

            $invSecond = VendorInventory::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('vendor_id', $vendorId)
                ->where('sku', $sku)
                ->where('location_id', $secondWhId)
                ->lockForUpdate()
                ->first();

            $invFrom = $fromWarehouseId === $firstWhId ? $invFirst : $invSecond;
            $invTo = $toWarehouseId === $firstWhId ? $invFirst : $invSecond;

            if ($invFrom === null || $invFrom->available() < $quantity) {
                throw new CodedHttpException(409, 'conflict', 'Insufficient available stock for transfer.');
            }

            if ($invTo === null) {
                $invTo = new VendorInventory();
                $invTo->forceFill([
                    'tenant_id' => $tenantId,
                    'vendor_id' => $vendorId,
                    'sku' => $sku,
                    'location_id' => $toWarehouseId,
                    'on_hand' => 0,
                    'reserved' => 0,
                    'version' => 1,
                ]);
            }

            $invFrom->on_hand -= $quantity;
            $invFrom->version += 1;
            $invFrom->save();

            $invTo->on_hand += $quantity;
            $invTo->version += 1;
            $invTo->save();

            // Append paired transfer movements
            $mvOut = new InventoryMovement();
            $mvOut->forceFill([
                'tenant_id' => $tenantId,
                'sku' => $sku,
                'location_id' => $fromWarehouseId,
                'movement_type' => InventoryMovement::TYPE_TRANSFER_OUT,
                'delta_signed' => -$quantity,
                'balance_after' => $invFrom->available(),
                'reference_type' => 'warehouse_transfer',
                'reference_id' => $toWarehouseId,
                'actor_id' => $actorId,
            ]);
            $mvOut->save();

            $mvIn = new InventoryMovement();
            $mvIn->forceFill([
                'tenant_id' => $tenantId,
                'sku' => $sku,
                'location_id' => $toWarehouseId,
                'movement_type' => InventoryMovement::TYPE_TRANSFER_IN,
                'delta_signed' => $quantity,
                'balance_after' => $invTo->available(),
                'reference_type' => 'warehouse_transfer',
                'reference_id' => $fromWarehouseId,
                'actor_id' => $actorId,
            ]);
            $mvIn->save();
        });
    }

    /**
     * @return list<VendorWarehouse|null>
     */
    private function resolveCandidateWarehouses(string $tenantId, string $vendorId, ?string $warehouseId): array
    {
        if ($warehouseId !== null) {
            $wh = VendorWarehouse::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('vendor_id', $vendorId)
                ->where('id', $warehouseId)
                ->where('is_active', true)
                ->first();

            return $wh !== null ? [$wh] : [];
        }

        $warehouses = VendorWarehouse::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get()
            ->all();

        if (empty($warehouses)) {
            // Null location fallback for legacy non-warehouse inventory
            return [null];
        }

        return $warehouses;
    }
}
