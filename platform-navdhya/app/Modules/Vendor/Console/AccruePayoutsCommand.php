<?php

namespace App\Modules\Vendor\Console;

use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorOrder;
use App\Modules\Vendor\Models\VendorPayout;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Accrues read-only payout projections per vendor per period from delivered orders (D6).
 */
final class AccruePayoutsCommand extends Command
{
    protected $signature = 'vendor:accrue-payouts {--period= : Period in YYYY-MM format (defaults to current month)} {--tenant= : Scope to specific tenant}';

    protected $description = 'Accrues read-only pending vendor payout projections based on delivered orders.';

    public function handle(Outbox $outbox): int
    {
        $period = $this->option('period') ?: now()->format('Y-m');
        $tenantId = $this->option('tenant');

        $vendorQuery = Vendor::withoutGlobalScopes();
        if ($tenantId !== null && $tenantId !== '') {
            $vendorQuery->where('tenant_id', $tenantId);
        }

        $vendors = $vendorQuery->get();
        $this->info("Accruing payouts for period {$period} across {$vendors->count()} vendors...");

        $startOfMonth = CarbonImmutable::createFromFormat('Y-m', $period)->startOfMonth();
        $endOfMonth = $startOfMonth->endOfMonth();

        $accrued = 0;

        foreach ($vendors as $vendor) {
            $deliveredOrdersTotal = (int) VendorOrder::withoutGlobalScopes()
                ->where('tenant_id', $vendor->tenant_id)
                ->where('vendor_id', $vendor->getKey())
                ->where('status', VendorOrder::STATUS_DELIVERED)
                ->whereBetween('updated_at', [$startOfMonth->toDateTimeString(), $endOfMonth->toDateTimeString()])
                ->sum('total_minor');

            if ($deliveredOrdersTotal <= 0) {
                continue;
            }

            /** @var VendorPayout|null $payout */
            $payout = VendorPayout::withoutGlobalScopes()
                ->where('tenant_id', $vendor->tenant_id)
                ->where('vendor_id', $vendor->getKey())
                ->where('period', $period)
                ->first();

            $isNew = false;
            if ($payout === null) {
                $payout = new VendorPayout();
                $payout->forceFill([
                    'tenant_id' => $vendor->tenant_id,
                    'vendor_id' => $vendor->getKey(),
                    'period' => $period,
                    'status' => VendorPayout::STATUS_PENDING,
                    'currency' => 'INR',
                    'reference' => "accrual_{$period}_{$vendor->getKey()}",
                ]);
                $isNew = true;
            }

            // Upsert amount; Vendor never writes PAID (Finance module remains sole pay authority)
            if ($payout->status === VendorPayout::STATUS_PENDING) {
                $payout->amount_minor = $deliveredOrdersTotal;
                $payout->save();
                $accrued++;

                if ($isNew) {
                    $outbox->append(
                        $vendor->tenant_id,
                        'vendor_payout',
                        (string) $payout->getKey(),
                        'vendor.payout.recorded',
                        [
                            'payout_id' => (string) $payout->getKey(),
                            'vendor_id' => (string) $vendor->getKey(),
                            'period' => $period,
                            'amount_minor' => $deliveredOrdersTotal,
                            'status' => VendorPayout::STATUS_PENDING,
                        ]
                    );
                }
            }
        }

        $this->info("Accrued {$accrued} vendor payout projections for period {$period}.");
        return 0;
    }
}
