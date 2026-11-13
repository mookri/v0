<?php

namespace Tests\Modules\Vendor;

use App\Modules\Booking\Application\CreateReservationDraftUseCase;
use App\Modules\Booking\Application\InitiateCheckoutHoldUseCase;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Vendor\Contracts\ManagesVendorOrders;
use App\Modules\Vendor\Models\Shipment;
use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorInventory;
use App\Modules\Vendor\Models\VendorWarehouse;
use Database\Factories\PartnerApplicationFactory;
use Database\Factories\VendorFactory;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class VendorDispatchGateTest extends TenantIntegrationTestCase
{
    public function test_vendor_cannot_dispatch_order_before_payment_confirmed(): void
    {
        $tenant = $this->makeTenant();
        [$user, $membership] = $this->actingAsMembership($tenant);
        $this->resolveContext($tenant, $membership);
        $headers = $this->tenantHeaders($tenant);

        // 1. Setup Vendor
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
            'code' => 'WH-TEST',
            'name' => 'Dispatch Warehouse',
            'city' => 'Haridwar',
            'state' => 'Uttarakhand',
            'is_default' => true,
            'is_active' => true,
        ]);

        $inv = VendorInventory::create([
            'tenant_id' => (string) $tenant->getKey(),
            'vendor_id' => (string) $vendor->getKey(),
            'sku' => 'KIT-DISPATCH-1',
            'location_id' => (string) $wh->getKey(),
            'on_hand' => 10,
            'reserved' => 0,
        ]);

        CatalogItem::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'sku' => 'SRV-DISPATCH-TEST',
            'name' => 'Puja Service',
            'status' => 'ACTIVE',
            'currency' => 'INR',
            'price_minor' => 200000,
        ]);

        // 2. Create Draft & Hold (Booking is still PENDING_PAYMENT, not CONFIRMED)
        /** @var CreateReservationDraftUseCase $draftUseCase */
        $draftUseCase = $this->app->make(CreateReservationDraftUseCase::class);
        /** @var InitiateCheckoutHoldUseCase $holdUseCase */
        $holdUseCase = $this->app->make(InitiateCheckoutHoldUseCase::class);

        $booking = $draftUseCase->execute(
            (string) $tenant->getKey(),
            (string) $user->getAuthIdentifier(),
            (string) Str::uuid(),
            [
                'service_sku' => 'SRV-DISPATCH-TEST',
                'scheduled_at' => now()->addDays(2)->toDateTimeString(),
                'duration_minutes' => 60,
                'kit_sku' => 'KIT-DISPATCH-1',
                'kit_quantity' => 1,
                'selected_vendor_id' => (string) $vendor->getKey(),
            ]
        );

        $holdUseCase->execute(
            (string) $tenant->getKey(),
            (string) $booking->getKey(),
            (string) $user->getAuthIdentifier(),
            15
        );

        // Manually place order linked to this unconfirmed booking
        /** @var ManagesVendorOrders $ordersPort */
        $ordersPort = $this->app->make(ManagesVendorOrders::class);
        $orderData = $ordersPort->place((string) $vendor->getKey(), [
            'reference' => 'bkg_' . $booking->getKey(),
            'customer_name' => (string) $user->getAuthIdentifier(),
            'currency' => 'INR',
            'items' => [
                ['sku' => 'KIT-DISPATCH-1', 'name' => 'Dispatch Kit', 'quantity' => 1, 'unit_minor' => 100000],
            ],
        ], (int) $user->getAuthIdentifier());

        $orderId = $orderData['id'];

        // Clear manual context so HTTP middleware ResolveTenantContext can set context cleanly
        app(\App\Platform\Tenancy\TenantContext::class)->clear();

        // 3. Vendor moves to PROCESSING, then PACKED
        $advanceUrl = "/api/v1/vendor/partner/vendors/{$vendor->getKey()}/orders/{$orderId}:advance";

        $respProc = $this->actingAs($user)->postJson($advanceUrl, ['status' => 'PROCESSING'], $headers);
        $respProc->assertOk()->assertJsonPath('data.status', 'PROCESSING');

        $respPacked = $this->actingAs($user)->postJson($advanceUrl, ['status' => 'PACKED'], $headers);
        $respPacked->assertOk()->assertJsonPath('data.status', 'PACKED');

        // 4. Attempt PACKED -> HANDOFF before booking is confirmed: must return 409 payment_not_confirmed
        $respHandoffFail = $this->actingAs($user)->postJson($advanceUrl, [
            'status' => 'HANDOFF',
            'carrier_name' => 'BlueDart Express',
            'tracking_code' => 'BD-123456789',
        ], $headers);

        $respHandoffFail->assertStatus(409)
            ->assertJsonPath('error.code', 'payment_not_confirmed');

        // 5. Customer pays / confirms booking
        $this->actingAs($user)->postJson(
            "/api/v2/booking/reservations/{$booking->getKey()}/confirm",
            [],
            array_merge($headers, [
                'If-Match' => 'W/"v2"',
                'Idempotency-Key' => (string) Str::uuid(),
            ])
        )->assertOk();

        // 6. Now advancing to HANDOFF succeeds!
        $respHandoffSuccess = $this->actingAs($user)->postJson($advanceUrl, [
            'status' => 'HANDOFF',
            'carrier_name' => 'BlueDart Express',
            'tracking_code' => 'BD-123456789',
        ], $headers);

        $respHandoffSuccess->assertOk()->assertJsonPath('data.status', 'HANDOFF');

        // Assert Shipment row exists
        $shipment = Shipment::withoutGlobalScopes()->where('order_id', $orderId)->first();
        $this->assertNotNull($shipment);
        $this->assertEquals('HANDOFF', $shipment->status);
        $this->assertEquals('BlueDart Express', $shipment->carrier_name);
        $this->assertEquals('BD-123456789', $shipment->tracking_code);

        // 7. Customer can track shipment via GET /api/v2/booking/reservations/{id}/shipment
        $trackingResp = $this->actingAs($user)->getJson("/api/v2/booking/reservations/{$booking->getKey()}/shipment", $headers);
        $trackingResp->assertOk()
            ->assertJsonPath('data.status', 'HANDOFF')
            ->assertJsonPath('data.carrier_name', 'BlueDart Express')
            ->assertJsonPath('data.tracking_code', 'BD-123456789');

        // 8. Move to IN_TRANSIT, then DELIVERED with pod_note
        $this->actingAs($user)->postJson($advanceUrl, ['status' => 'IN_TRANSIT'], $headers)->assertOk();

        // Attempt DELIVERED without pod_note fails 422
        $this->actingAs($user)->postJson($advanceUrl, ['status' => 'DELIVERED'], $headers)->assertStatus(422);

        // DELIVERED with pod_note succeeds
        $this->actingAs($user)->postJson($advanceUrl, [
            'status' => 'DELIVERED',
            'pod_note' => 'Delivered to recipient at doorstep. Signed by customer.',
        ], $headers)->assertOk()->assertJsonPath('data.status', 'DELIVERED');
    }
}
