<?php

namespace Tests\Modules\Booking;

use App\Modules\Booking\Domain\Exceptions\ResourceUnavailableException;
use App\Modules\Booking\Domain\StockReservationManager;
use App\Modules\Booking\Models\StockReservation;
use App\Modules\Vendor\Models\VendorInventory;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Stock Reservation Tests (Phase 2, Task 2.2.4).
 */
final class StockReservationManagerTest extends TenantIntegrationTestCase
{
    private StockReservationManager $manager;
    private string $tenantId;
    private string $vendorId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new StockReservationManager();
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant);
        $this->tenantId = (string) $tenant->getKey();

        $inventory = VendorInventory::factory()->create([
            'tenant_id' => $this->tenantId,
            'sku' => 'HAVAN-KIT-PREMIUM',
            'on_hand' => 5,
        ]);
        $this->vendorId = (string) $inventory->vendor_id;
    }

    public function test_hold_stock_verifies_on_hand_and_blocks_over_reservation(): void
    {
        $expires = CarbonImmutable::now()->addMinutes(15);

        // Hold 3 units out of 5
        $res1 = $this->manager->holdStock(
            $this->tenantId,
            $this->vendorId,
            'HAVAN-KIT-PREMIUM',
            'bkg-1',
            null,
            3,
            $expires
        );

        $this->assertSame(StockReservation::STATUS_HELD, $res1->status);
        $this->assertSame(3, $res1->units);

        // Second hold of 2 units (total = 5, exactly on hand)
        $res2 = $this->manager->holdStock(
            $this->tenantId,
            $this->vendorId,
            'HAVAN-KIT-PREMIUM',
            'bkg-2',
            null,
            2,
            $expires
        );
        $this->assertSame(2, $res2->units);

        // Third hold of 1 unit exceeds on-hand limit
        $this->expectException(ResourceUnavailableException::class);
        $this->expectExceptionMessage("Insufficient stock for kit SKU 'HAVAN-KIT-PREMIUM'");

        $this->manager->holdStock(
            $this->tenantId,
            $this->vendorId,
            'HAVAN-KIT-PREMIUM',
            'bkg-3',
            null,
            1,
            $expires
        );
    }

    public function test_commit_and_consume_stock_decrements_vendor_inventory(): void
    {
        $expires = CarbonImmutable::now()->addMinutes(15);

        $this->manager->holdStock(
            $this->tenantId,
            $this->vendorId,
            'HAVAN-KIT-PREMIUM',
            'bkg-consume',
            null,
            2,
            $expires
        );

        $this->manager->commitStock($this->tenantId, 'bkg-consume');

        $consumed = $this->manager->consumeStock($this->tenantId, 'bkg-consume');
        $this->assertSame(1, $consumed);

        $inventory = VendorInventory::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->where('vendor_id', $this->vendorId)
            ->where('sku', 'HAVAN-KIT-PREMIUM')
            ->first();

        $this->assertNotNull($inventory);
        $this->assertSame(3, $inventory->on_hand, 'on_hand must decrease from 5 to 3 upon ritual completion.');
    }

    public function test_release_stock_frees_reserved_units(): void
    {
        $expires = CarbonImmutable::now()->addMinutes(15);

        $this->manager->holdStock(
            $this->tenantId,
            $this->vendorId,
            'HAVAN-KIT-PREMIUM',
            'bkg-release',
            null,
            5,
            $expires
        );

        // All 5 units reserved.
        $this->manager->releaseStock($this->tenantId, 'bkg-release');

        // Can hold 5 units again now that previous hold is released
        $newHold = $this->manager->holdStock(
            $this->tenantId,
            $this->vendorId,
            'HAVAN-KIT-PREMIUM',
            'bkg-new',
            null,
            5,
            $expires
        );

        $this->assertNotNull($newHold);
        $this->assertSame(5, $newHold->units);
    }
}
