<?php

namespace Tests\Modules\Booking;

use App\Modules\Booking\Application\ConfirmBookingFulfillmentUseCase;
use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Booking\Application\InitiateCheckoutHoldUseCase;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\StockReservation;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorInventory;
use App\Modules\Vendor\Models\VendorOrder;
use App\Modules\Vendor\Models\VendorWarehouse;
use Database\Factories\PartnerApplicationFactory;
use Database\Factories\VendorFactory;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class BookingKitFulfillmentTest extends TenantIntegrationTestCase
{
    public function test_booking_draft_with_kit_holds_stock_and_confirms_with_vendor_order(): void
    {
        $tenant = $this->makeTenant();
        [$user, $membership] = $this->actingAsMembership($tenant);
        $this->resolveContext($tenant, $membership);

        // 1. Setup Vendor with inventory
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
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
            'city' => 'Varanasi',
            'state' => 'UP',
            'is_default' => true,
            'is_active' => true,
        ]);

        $inv = VendorInventory::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'sku' => 'KIT-RITUAL-STD',
            'location_id' => (string) $wh->getKey(),
            'on_hand' => 10,
            'reserved' => 0,
        ]);

        // 2. Setup Catalog Service
        CatalogItem::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'sku' => 'SRV-SATYANARAYAN',
            'name' => 'Satyanarayan Puja',
            'status' => 'ACTIVE',
            'currency' => 'INR',
            'price_minor' => 500000,
        ]);

        /** @var CreateReservationDraftUseCase $draftUseCase */
        $draftUseCase = $this->app->make(CreateReservationDraftUseCase::class);
        /** @var InitiateCheckoutHoldUseCase $holdUseCase */
        $holdUseCase = $this->app->make(InitiateCheckoutHoldUseCase::class);
        /** @var ConfirmBookingFulfillmentUseCase $confirmUseCase */
        $confirmUseCase = $this->app->make(ConfirmBookingFulfillmentUseCase::class);

        // 3. Create Draft with Kit line
        $booking = $draftUseCase->execute(
            (string) $tenant->getKey(),
            (string) $user->getAuthIdentifier(),
            (string) Str::uuid(),
            [
                'service_sku' => 'SRV-SATYANARAYAN',
                'scheduled_at' => now()->addDays(2)->toDateTimeString(),
                'duration_minutes' => 60,
                'kit_sku' => 'KIT-RITUAL-STD',
                'kit_quantity' => 2,
                'selected_vendor_id' => (string) $vendor->getKey(),
            ]
        );

        $this->assertEquals(BookingLifecycle::STATUS_DRAFT, $booking->status);

        // 4. Initiate Checkout Hold
        $heldBooking = $holdUseCase->execute(
            (string) $tenant->getKey(),
            (string) $booking->getKey(),
            (string) $user->getAuthIdentifier(),
            15
        );

        $this->assertEquals(BookingLifecycle::STATUS_PENDING_PAYMENT, $heldBooking->status);

        $inv->refresh();
        $this->assertEquals(2, $inv->reserved);
        $this->assertEquals(8, $inv->available());

        // Assert StockReservation mirror created in Booking
        $stockRes = StockReservation::withoutGlobalScopes()
            ->where('booking_id', (string) $booking->getKey())
            ->first();
        $this->assertNotNull($stockRes);
        $this->assertEquals(StockReservation::STATUS_HELD, $stockRes->status);

        // 5. Confirm Booking Fulfillment
        $confirmedBooking = $confirmUseCase->execute(
            (string) $tenant->getKey(),
            (string) $booking->getKey(),
            (string) $user->getAuthIdentifier(),
            'payment_confirmed'
        );

        $this->assertEquals(BookingLifecycle::STATUS_CONFIRMED, $confirmedBooking->status);

        // Stock consumed: on_hand decremented from 10 to 8, reserved cleared
        $inv->refresh();
        $this->assertEquals(8, $inv->on_hand);
        $this->assertEquals(0, $inv->reserved);

        // Assert Vendor Order auto-created for this vendor and booking reference
        $vendorOrder = VendorOrder::withoutGlobalScopes()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('vendor_id', (string) $vendor->getKey())
            ->where('reference', 'bkg_' . $booking->getKey())
            ->first();

        $this->assertNotNull($vendorOrder);
        $this->assertEquals(VendorOrder::STATUS_NEW, $vendorOrder->status);
    }
}
