<?php

namespace Tests\Modules\Vendor;

use App\Modules\Vendor\Contracts\ManagesStockReservations;
use App\Modules\Vendor\Models\InventoryMovement;
use App\Modules\Vendor\Models\VendorInventory;
use App\Modules\Vendor\Models\VendorWarehouse;
use App\Platform\Api\CodedHttpException;
use Database\Factories\PartnerApplicationFactory;
use Database\Factories\VendorFactory;
use Tests\TenantIntegrationTestCase;

final class VendorTransferConcurrencyTest extends TenantIntegrationTestCase
{
    public function test_transfers_between_warehouses_update_balances_and_log_movements(): void
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

        $whA = VendorWarehouse::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'code' => 'WH-A',
            'name' => 'Warehouse Alpha',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'is_default' => true,
            'is_active' => true,
        ]);

        $whB = VendorWarehouse::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'code' => 'WH-B',
            'name' => 'Warehouse Beta',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'is_default' => false,
            'is_active' => true,
        ]);

        $invA = VendorInventory::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'sku' => 'TRANSFER-SKU',
            'location_id' => (string) $whA->getKey(),
            'on_hand' => 100,
            'reserved' => 10,
        ]);

        $invB = VendorInventory::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'sku' => 'TRANSFER-SKU',
            'location_id' => (string) $whB->getKey(),
            'on_hand' => 20,
            'reserved' => 0,
        ]);

        /** @var ManagesStockReservations $stockService */
        $stockService = $this->app->make(ManagesStockReservations::class);

        // Transfer 30 units from A to B (A available = 90 >= 30)
        $stockService->transferStock(
            (string) $tenant->getKey(),
            (string) $vendor->getKey(),
            'TRANSFER-SKU',
            (string) $whA->getKey(),
            (string) $whB->getKey(),
            30,
            'operator_1'
        );

        $invA->refresh();
        $invB->refresh();

        $this->assertEquals(70, $invA->on_hand);
        $this->assertEquals(10, $invA->reserved);
        $this->assertEquals(60, $invA->available());

        $this->assertEquals(50, $invB->on_hand);
        $this->assertEquals(0, $invB->reserved);
        $this->assertEquals(50, $invB->available());

        // Assert paired movements created
        $outMovements = InventoryMovement::withoutGlobalScopes()->where('movement_type', InventoryMovement::TYPE_TRANSFER_OUT)->count();
        $inMovements = InventoryMovement::withoutGlobalScopes()->where('movement_type', InventoryMovement::TYPE_TRANSFER_IN)->count();
        $this->assertEquals(1, $outMovements);
        $this->assertEquals(1, $inMovements);

        // Attempting to transfer more than available from B (available 50, request 60) must fail with 409
        $this->expectException(CodedHttpException::class);
        $stockService->transferStock(
            (string) $tenant->getKey(),
            (string) $vendor->getKey(),
            'TRANSFER-SKU',
            (string) $whB->getKey(),
            (string) $whA->getKey(),
            60,
            'operator_1'
        );
    }
}
