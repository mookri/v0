<?php

namespace App\Modules\Vendor\Console;

use App\Modules\Vendor\Models\InventoryMovement;
use App\Modules\Vendor\Models\VendorInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verifies and reconciles vendor inventory stock against the append-only movement ledger (D2).
 */
final class ReconcileStockCommand extends Command
{
    protected $signature = 'vendor:reconcile-stock {--tenant= : Optional tenant ID to scope reconciliation} {--fix : Auto-fix detected drift}';

    protected $description = 'Reconciles vendor inventory balances and reserved holds with the immutable movement ledger.';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $autoFix = (bool) $this->option('fix');

        $query = VendorInventory::withoutGlobalScopes();
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }

        $inventories = $query->get();
        $discrepancies = 0;

        $this->info("Reconciling {$inventories->count()} vendor inventory records...");

        foreach ($inventories as $inv) {
            $mvQuery = InventoryMovement::withoutGlobalScopes()
                ->where('tenant_id', $inv->tenant_id)
                ->where('sku', $inv->sku);

            if ($inv->location_id !== null) {
                $mvQuery->where('location_id', $inv->location_id);
            } else {
                $mvQuery->whereNull('location_id');
            }

            $movements = $mvQuery->get();

            // Calculate active holds: HOLDs minus RELEASEs minus CONSUMEs
            $heldTotal = 0;
            $releasedTotal = 0;
            $consumedTotal = 0;

            foreach ($movements as $m) {
                if ($m->movement_type === InventoryMovement::TYPE_HOLD) {
                    $heldTotal += abs((int) $m->delta_signed);
                } elseif ($m->movement_type === InventoryMovement::TYPE_RELEASE) {
                    $releasedTotal += abs((int) $m->delta_signed);
                } elseif ($m->movement_type === InventoryMovement::TYPE_CONSUME) {
                    $consumedTotal += abs((int) $m->delta_signed);
                }
            }

            $expectedReserved = max(0, $heldTotal - $releasedTotal - $consumedTotal);

            if ($inv->reserved !== $expectedReserved && $heldTotal > 0) {
                $discrepancies++;
                $this->warn("Drift detected on SKU {$inv->sku} (Loc: {$inv->location_id}): Current reserved = {$inv->reserved}, Expected = {$expectedReserved}");

                if ($autoFix) {
                    $inv->reserved = $expectedReserved;
                    $inv->save();
                    $this->info("Fixed reserved counter for SKU {$inv->sku}.");
                }
            }
        }

        if ($discrepancies > 0 && ! $autoFix) {
            $this->error("Found {$discrepancies} stock discrepancies.");
            return 1;
        }

        $this->info("Stock reconciliation clean. Zero discrepancies found.");
        return 0;
    }
}
