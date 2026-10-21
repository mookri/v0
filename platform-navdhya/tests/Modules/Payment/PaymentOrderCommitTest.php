<?php

namespace Tests\Modules\Payment;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Modules\Payment\Models\PaymentIntent;
use App\Platform\Features\FeatureFlags;
use Illuminate\Support\Facades\Schema;
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
