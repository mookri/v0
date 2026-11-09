<?php

namespace Tests\Modules\Vendor;

use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorOrder;
use App\Modules\Vendor\Models\VendorOrderItem;
use App\Modules\Vendor\Models\VendorPayout;
use Database\Factories\PartnerApplicationFactory;
use Database\Factories\VendorFactory;
use Illuminate\Support\Facades\Artisan;
use Tests\TenantIntegrationTestCase;

final class VendorPayoutAccrualTest extends TenantIntegrationTestCase
{
    public function test_accrue_payouts_command_calculates_delivered_orders(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $app = PartnerApplicationFactory::new()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'identity_id' => (string) $user->getAuthIdentifier(),
            'track' => 'partner-vendor',
            'status' => 'APPROVED',
        ]);
        $vendor = VendorFactory::new()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'application_id' => (string) $app->getKey(),
            'owner_user_id' => (int) $user->getAuthIdentifier(),
        ]);

        $period = now()->format('Y-m');

        // Order 1: DELIVERED, 15000 minor
        $order1 = new VendorOrder();
        $order1->forceFill([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'reference' => 'ref_payout_1',
            'status' => VendorOrder::STATUS_DELIVERED,
            'currency' => 'INR',
            'total_minor' => 15000,
        ]);
        $order1->save();

        // Order 2: PROCESSING (not delivered), 25000 minor
        $order2 = new VendorOrder();
        $order2->forceFill([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'reference' => 'ref_payout_2',
            'status' => VendorOrder::STATUS_PROCESSING,
            'currency' => 'INR',
            'total_minor' => 25000,
        ]);
        $order2->save();

        // Run accrue command
        $code = Artisan::call('vendor:accrue-payouts', [
            '--period' => $period,
            '--tenant' => (string) $tenant->getKey(),
        ]);
        $this->assertEquals(0, $code);

        $payout = VendorPayout::withoutGlobalScopes()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('vendor_id', (string) $vendor->getKey())
            ->where('period', $period)
            ->first();

        $this->assertNotNull($payout);
        $this->assertEquals(VendorPayout::STATUS_PENDING, $payout->status);
        $this->assertEquals(15000, $payout->amount_minor);

        // Deliver order 2 and re-run: idempotent update
        $order2->status = VendorOrder::STATUS_DELIVERED;
        $order2->save();

        Artisan::call('vendor:accrue-payouts', [
            '--period' => $period,
            '--tenant' => (string) $tenant->getKey(),
        ]);

        $payout->refresh();
        $this->assertEquals(40000, $payout->amount_minor);
    }
}
