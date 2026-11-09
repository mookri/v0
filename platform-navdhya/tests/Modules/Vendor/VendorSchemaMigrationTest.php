<?php

namespace Tests\Modules\Vendor;

use App\Modules\Vendor\Models\InventoryMovement;
use App\Modules\Vendor\Models\Shipment;
use App\Modules\Vendor\Models\ShipmentEvent;
use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorInventory;
use App\Modules\Vendor\Models\VendorKitComponent;
use App\Modules\Vendor\Models\VendorWarehouse;
use Database\Factories\PartnerApplicationFactory;
use Database\Factories\VendorFactory;
use Illuminate\Support\Facades\Schema;
use Tests\TenantIntegrationTestCase;

final class VendorSchemaMigrationTest extends TenantIntegrationTestCase
{
    public function test_schema_has_multi_warehouse_and_kit_tables(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant);

        $this->assertTrue(Schema::connection('tenant_shared')->hasTable('vendor_warehouses'));
        $this->assertTrue(Schema::connection('tenant_shared')->hasTable('inventory_movements'));
        $this->assertTrue(Schema::connection('tenant_shared')->hasTable('vendor_kit_components'));
        $this->assertTrue(Schema::connection('tenant_shared')->hasTable('shipments'));
        $this->assertTrue(Schema::connection('tenant_shared')->hasTable('shipment_events'));

        $this->assertTrue(Schema::connection('tenant_shared')->hasColumn('vendor_inventories', 'location_id'));
        $this->assertTrue(Schema::connection('tenant_shared')->hasColumn('vendor_inventories', 'reserved'));
        $this->assertTrue(Schema::connection('tenant_shared')->hasColumn('vendor_inventories', 'version'));
    }

    public function test_can_create_and_query_warehouses_and_kit_components(): void
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

        $wh = VendorWarehouse::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'code' => 'WH-BLR-01',
            'name' => 'Bengaluru Central Depot',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertNotEmpty($wh->getKey());
        $this->assertTrue($wh->is_default);

        $comp = VendorKitComponent::create([
            'tenant_id' => (string) $tenant->getKey(),
            'kit_sku' => 'KIT-SATYANARAYAN',
            'component_sku' => 'COMP-CAMPHOR-100G',
            'vendor_id' => (string) $vendor->getKey(),
            'quantity' => 2,
        ]);

        $this->assertEquals(2, $comp->quantity);

        $inv = VendorInventory::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'sku' => 'COMP-CAMPHOR-100G',
            'location_id' => (string) $wh->getKey(),
            'on_hand' => 50,
            'reserved' => 5,
        ]);

        $this->assertEquals(45, $inv->available());

        $movement = InventoryMovement::create([
            'tenant_id' => (string) $tenant->getKey(),
            'sku' => 'COMP-CAMPHOR-100G',
            'location_id' => (string) $wh->getKey(),
            'movement_type' => InventoryMovement::TYPE_HOLD,
            'delta_signed' => -5,
            'balance_after' => 45,
            'reference_type' => 'booking',
            'reference_id' => 'bkg_123',
        ]);

        $this->assertEquals(-5, $movement->delta_signed);
    }
}
