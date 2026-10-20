<?php

namespace Tests\Modules\Payment;

use App\Models\User;
use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Modules\Payment\Models\PaymentIntent;
use App\Platform\Features\FeatureFlags;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Checkout (B3): :confirm begins payment for a DRAFT booking. It is
 * flag-gated fail-closed (404 while payments.enabled is off — the
 * surface does not exist), re-quotes through the catalog quote port
 * (amounts snapshotted at confirm time, never client-supplied), opens
 * exactly one provider order per booking (idempotent replay, unique
 * index), and fails closed before any state change when the provider is
 * unreachable.
 *
 * Requirement: B3 — payment_intents, :confirm DRAFT → PENDING_PAYMENT
 * (idempotent), amounts snapshotted via QuoteCalculator, flag-gated.
 */
final class PaymentCheckoutTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Tenant, 1: User, 2: string, 3: string}
     */
    private function checkoutFixture(int $priceMinor = 450000): array
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tenantId = (string) $tenant->getKey();

        CatalogItem::factory()->create([
            'tenant_id' => $tenantId,
            'sku' => 'CHECKOUT-PUJA',
            'status' => 'ACTIVE',
            'currency' => 'INR',
            'price_minor' => $priceMinor,
        ]);

        (new CatalogPrice())->forceFill([
            'tenant_id' => $tenantId,
            'sku' => 'CHECKOUT-PUJA',
            'market' => 'in',
            'currency' => 'INR',
            'pandit_id' => null,
            'price_minor' => $priceMinor,
            'effective_from' => now()->subDay()->toDateTimeString(),
        ])->save();

        $booking = Booking::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'service_sku' => 'CHECKOUT-PUJA',
            'status' => BookingLifecycle::STATUS_DRAFT,
        ]);

        return [$tenant, $user, $tenantId, (string) $booking->getKey()];
    }

    private function enablePayments(): void
    {
        config(['payments.driver' => 'fake']);
        $this->app->make(FeatureFlags::class)->setDefault('payments.enabled', true);
    }

    public function test_confirm_is_absent_while_the_payments_flag_is_off(): void
    {
        config(['payments.driver' => 'fake']); // flag stays at its seeded default: off
        [$tenant, $user, , $bookingId] = $this->checkoutFixture();
        $headers = $this->tenantHeaders($tenant);

        // Fail-closed (doc 07 §10 rule 1): while off, checkout is a 404 —
        // no oracle, no intent, no order.
        $this->actingAs($user)->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        $this->assertSame(0, PaymentIntent::query()->count());

        // The operator path turns it on (flags:set surface, F3 rule 4 —
        // there is no HTTP flag surface).
        $this->enablePayments();

        $this->actingAs($user)->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertCreated();
    }

    public function test_confirm_snapshots_the_requote_and_moves_draft_to_pending_payment(): void
    {
        $this->enablePayments();
        [$tenant, $user, $tenantId, $bookingId] = $this->checkoutFixture(450000);
        $headers = $this->tenantHeaders($tenant);

        $response = $this->actingAs($user)->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertCreated()
            ->assertJsonPath('data.object', 'payment_intent')
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.amount_minor', 450000)
            ->assertJsonPath('data.currency', 'INR')
            ->assertJsonPath('data.booking_id', $bookingId)
            ->assertJsonPath('data.test_mode', true);

        $this->assertStringStartsWith(
            'order_fake_',
            (string) $response->json('data.provider_order_id'),
            'the fake driver mints offline order ids (no network in gates)',
        );

        $booking = Booking::withoutGlobalScopes()->find($bookingId);
        $this->assertSame(BookingLifecycle::STATUS_PENDING_PAYMENT, $booking->status);
        $this->assertSame(450000, (int) $booking->total_minor);
        $this->assertSame('INR', $booking->currency);
        $this->assertSame(2, (int) $booking->version);

        $this->assertDatabaseHas('booking_transitions', [
            'booking_id' => $bookingId,
            'from_status' => BookingLifecycle::STATUS_DRAFT,
            'to_status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
            'reason' => 'payment_initiated',
        ], 'tenant_shared');

        $this->assertAuditRecorded($tenant, 'booking.payment.initiated');
        $this->assertAuditRecorded($tenant, 'payment.intent.created');

        // Same-tx producer: the outbox fact rides the confirm transaction.
        $this->assertSame(
            1,
            OutboxEvent::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('event_type', 'payment.intent.created')
                ->count(),
        );
    }

    public function test_confirm_is_idempotent_per_booking(): void
    {
        $this->enablePayments();
        [$tenant, $user, , $bookingId] = $this->checkoutFixture();
        $headers = $this->tenantHeaders($tenant);

        $first = $this->actingAs($user)->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertCreated();
        $second = $this->actingAs($user)->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertCreated();

        // Safe retries return the SAME intent and the same provider order.
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->json('data.provider_order_id'), $second->json('data.provider_order_id'));

        // One intent row (unique (tenant, booking) index), no re-transition,
        // no second version bump, no second outbox fact.
        $this->assertSame(1, PaymentIntent::query()->where('booking_id', $bookingId)->count());
        $this->assertSame(2, (int) Booking::withoutGlobalScopes()->find($bookingId)->version);
        $this->assertSame(
            1,
            BookingTransition::withoutGlobalScopes()->where('booking_id', $bookingId)->count(),
        );
    }

    public function test_amount_is_the_confirm_time_quote_not_the_draft_price(): void
    {
        $this->enablePayments();
        [$tenant, $user, $tenantId, $bookingId] = $this->checkoutFixture(450000);
        $headers = $this->tenantHeaders($tenant);

        // Price supersede AFTER the draft existed (newest effective row
        // wins): checkout charges the confirm-time quote.
        (new CatalogPrice())->forceFill([
            'tenant_id' => $tenantId,
            'sku' => 'CHECKOUT-PUJA',
            'market' => 'in',
            'currency' => 'INR',
            'pandit_id' => null,
            'price_minor' => 500000,
            'effective_from' => now()->subMinute()->toDateTimeString(),
        ])->save();

        $this->actingAs($user)->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertCreated()
            ->assertJsonPath('data.amount_minor', 500000);

        $this->assertSame(500000, (int) Booking::withoutGlobalScopes()->find($bookingId)->total_minor);
        $this->assertSame(
            500000,
            (int) PaymentIntent::query()->where('booking_id', $bookingId)->sole()->amount_minor,
        );
    }

    public function test_confirm_refuses_states_that_are_not_draft_or_pending_payment(): void
    {
        $this->enablePayments();
        [$tenant, $user, , $bookingId] = $this->checkoutFixture();
        $headers = $this->tenantHeaders($tenant);

        // CONFIRMED: the money already settled — no second intent ever.
        Booking::withoutGlobalScopes()->whereKey($bookingId)->update(['status' => BookingLifecycle::STATUS_CONFIRMED]);
        $this->actingAs($user)->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'state_forbidden');

        // PENDING_PAYMENT without an intent row is operator territory —
        // never a silent re-open.
        Booking::withoutGlobalScopes()->whereKey($bookingId)->update(['status' => BookingLifecycle::STATUS_PENDING_PAYMENT]);
        $this->actingAs($user)->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $headers)
            ->assertStatus(409);

        $this->assertSame(0, PaymentIntent::query()->count());
    }

    public function test_confirm_hides_foreign_and_unauthenticated_calls(): void
    {
        $this->enablePayments();
        [$tenant, $user, , $bookingId] = $this->checkoutFixture();
        $headers = $this->tenantHeaders($tenant);
        $uri = "/api/v1/booking/reservations/{$bookingId}:confirm";

        // Another member's booking: 404 (no existence leak).
        $otherUser = User::factory()->create();
        $this->makeMembership($tenant, $otherUser);
        $this->actingAs($otherUser)->postJson($uri, [], $headers)->assertNotFound();

        // Unknown id: 404.
        $this->actingAs($user)
            ->postJson('/api/v1/booking/reservations/01ARZ3NDEKTSV4RRFFQ69G5FAV:confirm', [], $headers)
            ->assertNotFound();

        // Guest: 401 (drop the acting user the fixture authenticated —
        // the house pattern from BookingReservationTest).
        auth()->logout();
        $this->postJson($uri, [], $headers)->assertUnauthorized();

        $this->assertSame(0, PaymentIntent::query()->count());
    }

    public function test_gateway_unavailability_fails_closed_before_any_state_change(): void
    {
        // Razorpay driver with NO keys: createOrder returns null
        // (fail-closed) — the confirm must surface dependency_unavailable
        // and move nothing.
        config([
            'payments.driver' => 'razorpay',
            'payments.razorpay.key_id' => null,
            'payments.razorpay.key_secret' => null,
        ]);
        $this->app->make(FeatureFlags::class)->setDefault('payments.enabled', true);

        [$tenant, $user, $tenantId, $bookingId] = $this->checkoutFixture();

        $this->actingAs($user)
            ->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $this->tenantHeaders($tenant))
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'dependency_unavailable');

        $booking = Booking::withoutGlobalScopes()->find($bookingId);
        $this->assertSame(BookingLifecycle::STATUS_DRAFT, $booking->status);
        $this->assertNull($booking->total_minor);
        $this->assertSame(0, PaymentIntent::query()->count());
        $this->assertSame(0, OutboxEvent::withoutGlobalScopes()->where('tenant_id', $tenantId)->count());
    }

    public function test_unquotable_sku_fails_the_confirm_with_422(): void
    {
        $this->enablePayments();
        [$tenant, $user, , $bookingId] = $this->checkoutFixture();

        // Pricing withdrawn: the whole quote fails — never a zero-amount
        // intent (whole-quote failure, per the pricing rules).
        CatalogPrice::withoutGlobalScopes()->delete();

        $this->actingAs($user)
            ->postJson("/api/v1/booking/reservations/{$bookingId}:confirm", [], $this->tenantHeaders($tenant))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertSame(0, PaymentIntent::query()->count());
    }
}
