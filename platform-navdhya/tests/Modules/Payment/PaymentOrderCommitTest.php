<?php

namespace Tests\Modules\Payment;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Contracts\CalculatesQuotes;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Modules\Payment\Gateways\FakeGateway;
use App\Modules\Payment\Models\PaymentIntent;
use App\Platform\Features\FeatureFlags;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TenantIntegrationTestCase;

/**
 * The provider order is committed independently of the booking state
 * change (BACKEND-RCA 2026-10-21, finding W2).
 *
 * The order used to be opened INSIDE the booking transaction, so a
 * rollback destroyed the intent row while the order lived on at the
 * provider: an orphan the customer could still pay, and the retry
 * opened a SECOND order. The confirm now runs two committed
 * transactions - the intent first, then the state change - so a failed
 * state change leaves the intent (and the order) recorded.
 */
final class PaymentOrderCommitTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: \App\Platform\Tenancy\Models\Tenant, 1: User, 2: string}
     */
    private function checkoutFixture(): array
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tenantId = (string) $tenant->getKey();

        CatalogItem::factory()->create([
            'tenant_id' => $tenantId,
            'sku' => 'ORDER-COMMIT-PUJA',
            'status' => 'ACTIVE',
            'currency' => 'INR',
            'price_minor' => 450000,
        ]);

        (new CatalogPrice())->forceFill([
            'tenant_id' => $tenantId,
            'sku' => 'ORDER-COMMIT-PUJA',
            'market' => 'in',
            'currency' => 'INR',
            'pandit_id' => null,
            'price_minor' => 450000,
            'effective_from' => now()->subDay()->toDateTimeString(),
        ])->save();

        $booking = Booking::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'service_sku' => 'ORDER-COMMIT-PUJA',
            'status' => BookingLifecycle::STATUS_DRAFT,
        ]);

        return [$tenant, $user, (string) $booking->getKey()];
    }

    private function enablePayments(): void
    {
        config(['payments.driver' => 'fake']);
        $this->app->make(FeatureFlags::class)->setDefault('payments.enabled', true);
    }

    public function test_a_failed_state_change_keeps_the_committed_provider_order(): void
    {
        $this->enablePayments();
        [$tenant, $user, $bookingId] = $this->checkoutFixture();
        $headers = $this->tenantHeaders($tenant);

        // Break the transition write so the SECOND transaction fails
        // AFTER the intent (and the provider order) has committed.
        Schema::connection('tenant_shared')->drop('booking_transitions');

        $this->actingAs($user)
            ->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertStatus(500);

        // The intent row - and therefore the provider order - is COMMITTED.
        // A retry reuses that order instead of opening a second, payable
        // one.
        $intent = PaymentIntent::query()->where('booking_id', $bookingId)->first();

        $this->assertNotNull($intent, 'The payment intent must survive a failed state change.');
        $this->assertStringStartsWith('order_fake_', (string) $intent->provider_order_id);

        // The state change rolled back: the booking is still DRAFT.
        $this->assertSame(
            BookingLifecycle::STATUS_DRAFT,
            Booking::withoutGlobalScopes()->find($bookingId)->status,
        );
    }

    #[DataProvider('newerCatalogPrices')]
    public function test_a_retained_intent_keeps_the_original_booking_amount_and_currency(string $newCurrency, int $newPriceMinor): void
    {
        $this->enablePayments();
        [$tenant, $user, $bookingId] = $this->checkoutFixture();
        $tenantId = (string) $tenant->getKey();
        $this->resolveContext($tenant);
        $quotes = $this->app->make(CalculatesQuotes::class);
        $lines = [['sku' => 'ORDER-COMMIT-PUJA', 'quantity' => 1]];
        $originalQuote = $quotes->quote('INR', $lines, CarbonImmutable::now('UTC'));
        $this->assertTrue($originalQuote['ok']);
        $this->assertSame(450000, $originalQuote['total_minor']);

        $orderId = (new FakeGateway())->createOrder(450000, 'INR', 'booking_'.$bookingId);
        $intent = PaymentIntent::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'booking_id' => $bookingId,
            'amount_minor' => 450000,
            'currency' => 'INR',
            'status' => PaymentIntent::STATUS_PENDING,
            'provider' => 'fake',
            'test_mode' => true,
            'provider_order_id' => $orderId,
            'quote_snapshot' => $originalQuote,
        ]);
        $this->assertSame(BookingLifecycle::STATUS_DRAFT, Booking::query()->findOrFail($bookingId)->status);

        CatalogItem::query()->where('sku', 'ORDER-COMMIT-PUJA')->update([
            'currency' => $newCurrency,
            'price_minor' => $newPriceMinor,
        ]);
        (new CatalogPrice())->forceFill([
            'tenant_id' => $tenantId,
            'sku' => 'ORDER-COMMIT-PUJA',
            'market' => 'in',
            'currency' => $newCurrency,
            'pandit_id' => null,
            'price_minor' => $newPriceMinor,
            'effective_from' => now()->subMinute()->toDateTimeString(),
        ])->save();

        $newQuote = $quotes->quote($newCurrency, $lines, CarbonImmutable::now('UTC'));
        $this->assertTrue($newQuote['ok']);
        $this->assertSame($newPriceMinor, $newQuote['total_minor']);
        $this->assertSame($newCurrency, $newQuote['currency']);
        $this->clearContext();

        $this->actingAs($user)
            ->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $this->tenantHeaders($tenant))
            ->assertCreated()
            ->assertJsonPath('data.id', (string) $intent->getKey())
            ->assertJsonPath('data.provider_order_id', $orderId)
            ->assertJsonPath('data.booking_id', $bookingId)
            ->assertJsonPath('data.amount_minor', 450000)
            ->assertJsonPath('data.currency', 'INR');

        $retained = PaymentIntent::query()->where('tenant_id', $tenantId)->where('booking_id', $bookingId)->sole();
        $this->assertSame((string) $intent->getKey(), (string) $retained->getKey());
        $this->assertSame($orderId, $retained->provider_order_id);
        $this->assertSame($originalQuote, $retained->quote_snapshot);
        $this->assertSame(450000, (int) $retained->amount_minor);
        $this->assertSame('INR', $retained->currency);

        $booking = Booking::withoutGlobalScopes()->findOrFail($bookingId);
        $this->assertSame(BookingLifecycle::STATUS_PENDING_PAYMENT, $booking->status);
        $this->assertSame(2, (int) $booking->version);
        $this->assertSame(
            ['total_minor' => 450000, 'currency' => 'INR'],
            ['total_minor' => (int) $booking->total_minor, 'currency' => $booking->currency],
        );
    }

    public static function newerCatalogPrices(): array
    {
        return [
            'newer price in original currency' => ['INR', 500000],
            'newer price and currency' => ['USD', 9000],
        ];
    }

    public function test_a_confirm_on_a_non_draft_booking_opens_no_provider_order(): void
    {
        $this->enablePayments();
        [$tenant, $user, $bookingId] = $this->checkoutFixture();
        $headers = $this->tenantHeaders($tenant);

        Booking::withoutGlobalScopes()->find($bookingId)->forceFill([
            'status' => BookingLifecycle::STATUS_CANCELLED,
        ])->save();

        $this->actingAs($user)
            ->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertStatus(409);

        // No order was opened for a booking that cannot be paid.
        $this->assertSame(0, PaymentIntent::query()->where('booking_id', $bookingId)->count());
    }
}
