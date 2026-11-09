<?php

namespace Tests\Modules\Vendor;

use App\Modules\Vendor\Contracts\ManagesStockReservations;
use App\Modules\Vendor\Models\InventoryMovement;
use App\Modules\Vendor\Models\VendorInventory;
use App\Modules\Vendor\Models\VendorKitComponent;
use App\Modules\Vendor\Models\VendorWarehouse;
use Carbon\CarbonImmutable;
use Database\Factories\PartnerApplicationFactory;
use Database\Factories\VendorFactory;
use Illuminate\Support\Facades\Artisan;
use Tests\TenantIntegrationTestCase;

final class VendorStockLedgerTest extends TenantIntegrationTestCase
{
    private function setupVendorWithWarehouse($tenant, $user): array
    {
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

        $wh = VendorWarehouse::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'code' => 'WH-DEL-01',
            'name' => 'Delhi Hub',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'is_default' => true,
            'is_active' => true,
        ]);

        return [$vendor, $wh];
    }

    public function test_hold_kit_explodes_bom_and_locks_stock(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$vendor, $wh] = $this->setupVendorWithWarehouse($tenant, $user);

        // Kit BOM: 1 KIT = 2 Camphor + 1 Ghee
        VendorKitComponent::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'kit_sku' => 'KIT-HAVAN',
            'component_sku' => 'COMP-CAMPHOR',
            'quantity' => 2,
        ]);
        VendorKitComponent::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'kit_sku' => 'KIT-HAVAN',
            'component_sku' => 'COMP-GHEE',
            'quantity' => 1,
        ]);

        // Inventory
        $invCamphor = VendorInventory::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'sku' => 'COMP-CAMPHOR',
            'location_id' => (string) $wh->getKey(),
            'on_hand' => 10,
            'reserved' => 0,
        ]);
        $invGhee = VendorInventory::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'sku' => 'COMP-GHEE',
            'location_id' => (string) $wh->getKey(),
            'on_hand' => 5,
            'reserved' => 0,
        ]);

        /** @var ManagesStockReservations $stockService */
        $stockService = $this->app->make(ManagesStockReservations::class);

        // Request 3 Kits => needs 6 Camphor, 3 Ghee
        $expiresAt = CarbonImmutable::now()->addMinutes(15);
        $verdict = $stockService->holdKit(
            (string) $tenant->getKey(),
            (string) $vendor->getKey(),
            'KIT-HAVAN',
            3,
            'bkg_order_1',
            $expiresAt
        );

        $this->assertTrue($verdict->isSuccess());
        $this->assertEquals((string) $wh->getKey(), $verdict->warehouseId);

        $invCamphor->refresh();
        $invGhee->refresh();
        $this->assertEquals(6, $invCamphor->reserved);
        $this->assertEquals(4, $invCamphor->available());
        $this->assertEquals(3, $invGhee->reserved);
        $this->assertEquals(2, $invGhee->available());

        // Second hold for 3 more Kits => requires 6 Camphor (only 4 available) => must fail
        $verdict2 = $stockService->holdKit(
            (string) $tenant->getKey(),
            (string) $vendor->getKey(),
            'KIT-HAVAN',
            3,
            'bkg_order_2',
            $expiresAt
        );

        $this->assertFalse($verdict2->isSuccess());

        // Commit first hold
        $stockService->commitHold((string) $tenant->getKey(), 'bkg_order_1');

        $commitMovements = InventoryMovement::withoutGlobalScopes()
            ->where('reference_id', 'bkg_order_1')
            ->where('movement_type', InventoryMovement::TYPE_COMMIT)
            ->count();
        $this->assertEquals(2, $commitMovements);

        // Consume stock
        $stockService->consumeStock((string) $tenant->getKey(), 'bkg_order_1');
        $invCamphor->refresh();
        $invGhee->refresh();
        $this->assertEquals(4, $invCamphor->on_hand);
        $this->assertEquals(0, $invCamphor->reserved);
        $this->assertEquals(2, $invGhee->on_hand);
        $this->assertEquals(0, $invGhee->reserved);
    }

    public function test_release_hold_restores_available_stock(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$vendor, $wh] = $this->setupVendorWithWarehouse($tenant, $user);

        $inv = VendorInventory::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'sku' => 'SKU-SINGLE',
            'location_id' => (string) $wh->getKey(),
            'on_hand' => 5,
            'reserved' => 0,
        ]);

        /** @var ManagesStockReservations $stockService */
        $stockService = $this->app->make(ManagesStockReservations::class);
        $verdict = $stockService->holdKit(
            (string) $tenant->getKey(),
            (string) $vendor->getKey(),
            'SKU-SINGLE',
            4,
            'bkg_order_release',
            CarbonImmutable::now()->addMinutes(15)
        );

        $this->assertTrue($verdict->isSuccess());
        $inv->refresh();
        $this->assertEquals(4, $inv->reserved);

        $stockService->releaseHold((string) $tenant->getKey(), 'bkg_order_release', 'cancelled');
        $inv->refresh();
        $this->assertEquals(0, $inv->reserved);
        $this->assertEquals(5, $inv->available());
    }

    public function test_reconcile_command_runs_cleanly(): void
    {
        $exitCode = Artisan::call('vendor:reconcile-stock');
        $this->assertEquals(0, $exitCode);
    }
}
