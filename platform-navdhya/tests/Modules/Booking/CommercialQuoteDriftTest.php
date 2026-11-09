<?php

namespace Tests\Modules\Booking;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Commercial Quote Drift & Lock Suite (WP3).
 * Verifies quote endpoint calculation, hash locking, and 409 quote_stale drift protection.
 */
final class CommercialQuoteDriftTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User}
     */
    private function customer(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $user->forceFill(['external_id' => 'cust_' . strtolower((string) Str::ulid())])->save();
        $this->makeMembership($tenant, $user);

        $this->resolveContext($tenant);
        try {
            BuyerProfile::factory()->create(['user_id' => $user->id]);
        } finally {
            $this->clearContext();
        }

        $this->actingAs($user);

        return [$tenant, $user];
    }

    private function seedCatalogItemAndPrice(Tenant $tenant, string $sku, int $priceMinor): CatalogPrice
    {
        $this->resolveContext($tenant);
        try {
            CatalogItem::factory()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'sku' => $sku,
                'status' => 'ACTIVE',
                'currency' => 'INR',
                'price_minor' => $priceMinor,
            ]);

            return CatalogPrice::create([
                'tenant_id' => (string) $tenant->getKey(),
                'sku' => $sku,
                'currency' => 'INR',
                'market' => 'in',
                'pandit_id' => null,
                'price_minor' => $priceMinor,
                'effective_from' => CarbonImmutable::now()->subDays(1),
            ]);
        } finally {
            $this->clearContext();
        }
    }

    public function test_booking_quote_endpoint_computes_quote_and_sets_hash_and_amount(): void
    {
        [$tenant, $user] = $this->customer();
        $sku = 'puja_rudra_' . Str::random(6);
        $this->seedCatalogItemAndPrice($tenant, $sku, 500000);

        $headers = array_merge($this->tenantHeaders($tenant), [
            'Idempotency-Key' => (string) Str::uuid(),
        ]);

        // 1. Create draft reservation
        $createRes = $this->postJson('/api/v2/booking/reservations', [
            'service_sku' => $sku,
            'scheduled_at' => CarbonImmutable::now()->addDays(3)->toIso8601String(),
            'duration_minutes' => 60,
            'address_snapshot' => ['city' => 'Mumbai', 'state' => 'MH', 'postal_code' => '400001'],
        ], $headers)->assertStatus(201);

        $bookingId = $createRes->json('data.id');

        // 2. Request commercial quote
        $quoteRes = $this->postJson("/api/v2/booking/reservations/{$bookingId}/quote", [
            'customer_state' => 'MH',
        ], $headers);

        $quoteRes->assertOk()
            ->assertJsonPath('data.booking_id', $bookingId)
            ->assertJsonPath('data.currency', 'INR')
            ->assertJsonPath('data.jurisdiction', 'IN');

        $quoteHash = $quoteRes->json('data.quote_hash');
        $this->assertNotEmpty($quoteHash);
        $totalMinor = $quoteRes->json('data.summary.total_minor');
        $this->assertGreaterThan(500000, $totalMinor); // Base 500000 + 18% GST

        // Verify booking in database has quote_hash recorded
        $this->resolveContext($tenant);
        try {
            $booking = Booking::findOrFail($bookingId);
            $this->assertSame($quoteHash, $booking->accepted_quote_hash);
            $this->assertSame($totalMinor, $booking->total_minor);
        } finally {
            $this->clearContext();
        }

        // 3. Checkout with valid quote succeeds
        $checkoutRes = $this->postJson("/api/v2/booking/reservations/{$bookingId}/checkout", [], $headers);
        $checkoutRes->assertOk()
            ->assertJsonPath('data.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.accepted_quote_hash', $quoteHash);
    }

    public function test_booking_checkout_rejects_with_409_quote_stale_when_pricing_drifts(): void
    {
        [$tenant, $user] = $this->customer();
        $sku = 'puja_lakshmi_' . Str::random(6);
        $price = $this->seedCatalogItemAndPrice($tenant, $sku, 300000);

        $headers = array_merge($this->tenantHeaders($tenant), [
            'Idempotency-Key' => (string) Str::uuid(),
        ]);

        // 1. Create draft reservation
        $createRes = $this->postJson('/api/v2/booking/reservations', [
            'service_sku' => $sku,
            'scheduled_at' => CarbonImmutable::now()->addDays(5)->toIso8601String(),
            'duration_minutes' => 60,
            'address_snapshot' => ['city' => 'Pune', 'state' => 'MH'],
        ], $headers)->assertStatus(201);

        $bookingId = $createRes->json('data.id');

        // 2. Lock quote
        $quoteRes = $this->postJson("/api/v2/booking/reservations/{$bookingId}/quote", [], $headers);
        $quoteRes->assertOk();
        $initialHash = $quoteRes->json('data.quote_hash');

        // 3. Price drifts: catalog price changes
        $this->resolveContext($tenant);
        try {
            $price->price_minor = 450000;
            $price->save();
        } finally {
            $this->clearContext();
        }

        // 4. Checkout attempt detects stale quote hash and returns 409
        $checkoutRes = $this->postJson("/api/v2/booking/reservations/{$bookingId}/checkout", [], $headers);
        $checkoutRes->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'quote_stale')
            ->assertJsonPath('title', 'Quote Stale');

        // Verify booking status is still DRAFT
        $this->resolveContext($tenant);
        try {
            $booking = Booking::findOrFail($bookingId);
            $this->assertSame('DRAFT', $booking->status);
        } finally {
            $this->clearContext();
        }

        // 5. Re-quote to accept new commercial price
        $reQuoteRes = $this->postJson("/api/v2/booking/reservations/{$bookingId}/quote", [], $headers);
        $reQuoteRes->assertOk();
        $newHash = $reQuoteRes->json('data.quote_hash');
        $this->assertNotSame($initialHash, $newHash);

        // 6. Checkout now succeeds with new quote and new idempotency key
        $retryHeaders = array_merge($headers, ['Idempotency-Key' => (string) Str::uuid()]);
        $newCheckoutRes = $this->postJson("/api/v2/booking/reservations/{$bookingId}/checkout", [], $retryHeaders);
        $newCheckoutRes->assertOk()
            ->assertJsonPath('data.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.accepted_quote_hash', $newHash);
    }
}
