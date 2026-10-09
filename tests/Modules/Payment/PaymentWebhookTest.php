<?php

namespace Tests\Modules\Payment;

use App\Modules\Booking\Domain\BookingLifecycle;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingTransition;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Modules\Payment\Models\PaymentIntent;
use App\Platform\Audit\AuditLog;
use App\Platform\Features\FeatureFlags;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Testing\TestResponse;
use Tests\TenantIntegrationTestCase;

/**
 * Webhook (B3): the ONLY money-confirmation path. HMAC-SHA256 over the
 * RAW body — forged signatures are rejected before any parsing; each
 * leg applies through the booking lifecycle port (capture →
 * PAYMENT_CONFIRMED → CONFIRMED, failure → FAILED, refund → REFUNDED)
 * in one transaction with its outbox event and audits. Replays, unknown
 * orders/payments, money mismatches, unsupported events and
 * out-of-order legs answer 200 ignored and write NOTHING.
 *
 * Requirement: B3 — signature-verified replay-safe webhook, FAILED and
 * REFUNDED legs, audit on money transitions.
 */
final class PaymentWebhookTest extends TenantIntegrationTestCase
{
    private const SECRET = 'whsec_test_123';

    /**
     * Full checkout fixture: enabled payments, seeded catalog + price,
     * DRAFT booking driven through :confirm (fake gateway).
     *
     * @return array{0: Tenant, 1: string, 2: string, 3: string, 4: string, 5: int}
     */
    private function setUpCheckout(int $amountMinor = 450000): array
    {
        config(['payments.driver' => 'fake', 'payments.razorpay.webhook_secret' => self::SECRET]);
        $this->app->make(FeatureFlags::class)->setDefault('payments.enabled', true);

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tenantId = (string) $tenant->getKey();

        CatalogItem::factory()->create([
            'tenant_id' => $tenantId,
            'sku' => 'WEBHOOK-PUJA',
            'status' => 'ACTIVE',
            'currency' => 'INR',
            'price_minor' => $amountMinor,
        ]);

        (new CatalogPrice())->forceFill([
            'tenant_id' => $tenantId,
            'sku' => 'WEBHOOK-PUJA',
            'market' => 'in',
            'currency' => 'INR',
            'pandit_id' => null,
            'price_minor' => $amountMinor,
            'effective_from' => now()->subDay()->toDateTimeString(),
        ])->save();

        $booking = Booking::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'service_sku' => 'WEBHOOK-PUJA',
            'status' => BookingLifecycle::STATUS_DRAFT,
        ]);

        $confirmed = $this->actingAs($user)->postJson(
            '/api/v1/booking/reservations/'.(string) $booking->getKey().':confirm',
            [],
            $this->tenantHeaders($tenant),
        )->assertCreated();

        // System-path hygiene: the webhook has no ambient tenant.
        $this->clearContext();

        return [
            $tenant,
            $tenantId,
            (string) $booking->getKey(),
            (string) $confirmed->json('data.id'),
            (string) $confirmed->json('data.provider_order_id'),
            $amountMinor,
        ];
    }

    private function sendWebhook(string $body, ?string $signature = null): TestResponse
    {
        return $this->call('POST', '/api/v1/payment/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature ?? hash_hmac('sha256', $body, self::SECRET),
        ], $body);
    }

    private function capturedBody(string $orderId, int $amountMinor, string $currency = 'INR', string $paymentId = 'pay_test_0001'): string
    {
        return (string) json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => $paymentId, 'order_id' => $orderId, 'amount' => $amountMinor, 'currency' => $currency]]],
        ]);
    }

    private function refundBody(string $paymentId, int $amountMinor): string
    {
        return (string) json_encode([
            'event' => 'refund.processed',
            'payload' => ['refund' => ['entity' => ['id' => 'rfnd_test_1', 'payment_id' => $paymentId, 'amount' => $amountMinor, 'currency' => 'INR']]],
        ]);
    }

    private function auditCount(Tenant $tenant, string $action): int
    {
        return AuditLog::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->where('action', $action)
            ->count();
    }

    public function test_forged_signatures_are_rejected_before_any_parsing(): void
    {
        [, $tenantId, $bookingId, $intentId, $orderId, $amount] = $this->setUpCheckout();

        $this->sendWebhook($this->capturedBody($orderId, $amount), 'deadbeef')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->assertSame(PaymentIntent::STATUS_PENDING, PaymentIntent::query()->find($intentId)->status);
        $this->assertSame(BookingLifecycle::STATUS_PENDING_PAYMENT, Booking::withoutGlobalScopes()->find($bookingId)->status);
        $this->assertSame(0, OutboxEvent::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('event_type', 'payment.intent.confirmed')->count());
    }

    public function test_webhook_without_a_configured_secret_is_fully_closed(): void
    {
        config(['payments.razorpay.webhook_secret' => null]);

        $this->sendWebhook($this->capturedBody('order_x', 100))
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'dependency_unavailable');
    }

    public function test_valid_capture_confirms_intent_and_booking_in_one_fact(): void
    {
        [$tenant, $tenantId, $bookingId, $intentId, $orderId, $amount] = $this->setUpCheckout();

        $this->sendWebhook($this->capturedBody($orderId, $amount))
            ->assertOk()
            ->assertJsonPath('data.object', 'webhook_receipt')
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.intent_id', $intentId);

        $intent = PaymentIntent::query()->find($intentId);
        $this->assertSame(PaymentIntent::STATUS_SUCCEEDED, $intent->status);
        $this->assertSame('pay_test_0001', $intent->provider_payment_id);

        $booking = Booking::withoutGlobalScopes()->find($bookingId);
        $this->assertSame(BookingLifecycle::STATUS_CONFIRMED, $booking->status);
        $this->assertSame(3, (int) $booking->version); // 1→2 at :confirm, 2→3 at capture

        // Both matrix hops recorded with the system actor (the customer
        // did not request these transitions — K-029 attribution).
        $this->assertDatabaseHas('booking_transitions', [
            'booking_id' => $bookingId,
            'from_status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
            'to_status' => BookingLifecycle::STATUS_PAYMENT_CONFIRMED,
            'actor_id' => 'system:payment-webhook',
        ], 'tenant_shared');
        $this->assertDatabaseHas('booking_transitions', [
            'booking_id' => $bookingId,
            'from_status' => BookingLifecycle::STATUS_PAYMENT_CONFIRMED,
            'to_status' => BookingLifecycle::STATUS_CONFIRMED,
        ], 'tenant_shared');

        $this->assertAuditRecorded($tenant, 'booking.payment.confirmed');
        $this->assertAuditRecorded($tenant, 'payment.intent.confirmed');
        $this->assertSame(
            1,
            OutboxEvent::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('event_type', 'payment.intent.confirmed')
                ->count(),
        );
    }

    public function test_capture_replay_is_ignored_and_never_double_writes(): void
    {
        [$tenant, , $bookingId, $intentId, $orderId, $amount] = $this->setUpCheckout();
        $body = $this->capturedBody($orderId, $amount);

        $this->sendWebhook($body)->assertOk()->assertJsonPath('data.status', 'processed');

        $this->sendWebhook($body)
            ->assertOk()
            ->assertJsonPath('data.status', 'ignored')
            ->assertJsonPath('data.reason', 'replay');

        $this->assertSame(PaymentIntent::STATUS_SUCCEEDED, PaymentIntent::query()->find($intentId)->status);
        $this->assertSame(3, (int) Booking::withoutGlobalScopes()->find($bookingId)->version);
        $this->assertSame(3, BookingTransition::withoutGlobalScopes()->where('booking_id', $bookingId)->count());
        $this->assertSame(1, $this->auditCount($tenant, 'payment.intent.confirmed'));
        $this->assertSame(
            1,
            OutboxEvent::withoutGlobalScopes()->where('event_type', 'payment.intent.confirmed')->count(),
        );
    }

    public function test_amount_mismatch_never_confirms(): void
    {
        [$tenant, $tenantId, $bookingId, $intentId, $orderId, $amount] = $this->setUpCheckout();

        // Money guard: only the exact snapshot may ever be confirmed.
        $this->sendWebhook($this->capturedBody($orderId, $amount + 1))
            ->assertOk()
            ->assertJsonPath('data.status', 'ignored')
            ->assertJsonPath('data.reason', 'money_mismatch');

        $this->assertSame(PaymentIntent::STATUS_PENDING, PaymentIntent::query()->find($intentId)->status);
        $this->assertSame(BookingLifecycle::STATUS_PENDING_PAYMENT, Booking::withoutGlobalScopes()->find($bookingId)->status);
        $this->assertSame(0, $this->auditCount($tenant, 'payment.intent.confirmed'));
        $this->assertSame(0, OutboxEvent::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('event_type', 'payment.intent.confirmed')->count());
    }

    public function test_failed_payment_fails_the_leg(): void
    {
        [$tenant, $tenantId, $bookingId, $intentId, $orderId, $amount] = $this->setUpCheckout();

        $body = (string) json_encode([
            'event' => 'payment.failed',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_test_fail', 'order_id' => $orderId, 'amount' => $amount, 'currency' => 'INR']]],
        ]);

        $this->sendWebhook($body)
            ->assertOk()
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.intent_id', $intentId);

        $this->assertSame(PaymentIntent::STATUS_FAILED, PaymentIntent::query()->find($intentId)->status);
        $this->assertSame(BookingLifecycle::STATUS_FAILED, Booking::withoutGlobalScopes()->find($bookingId)->status);

        $this->assertDatabaseHas('booking_transitions', [
            'booking_id' => $bookingId,
            'from_status' => BookingLifecycle::STATUS_PENDING_PAYMENT,
            'to_status' => BookingLifecycle::STATUS_FAILED,
        ], 'tenant_shared');
        $this->assertAuditRecorded($tenant, 'booking.payment.failed');
        $this->assertAuditRecorded($tenant, 'payment.intent.failed');
        $this->assertSame(
            1,
            OutboxEvent::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('event_type', 'payment.intent.failed')->count(),
        );
    }

    public function test_refund_settles_only_after_capture(): void
    {
        [$tenant, $tenantId, $bookingId, $intentId, $orderId, $amount] = $this->setUpCheckout();

        // Out-of-order: a refund whose payment we have never recorded
        // cannot settle anything (no oracle, no writes).
        $this->sendWebhook($this->refundBody('pay_test_9999', $amount))
            ->assertOk()
            ->assertJsonPath('data.status', 'ignored')
            ->assertJsonPath('data.reason', 'unknown_payment');
        $this->assertSame(PaymentIntent::STATUS_PENDING, PaymentIntent::query()->find($intentId)->status);

        // Capture, then the full refund.
        $this->sendWebhook($this->capturedBody($orderId, $amount))->assertOk()->assertJsonPath('data.status', 'processed');

        $this->sendWebhook($this->refundBody('pay_test_0001', $amount))
            ->assertOk()
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.intent_id', $intentId);

        $this->assertSame(PaymentIntent::STATUS_REFUNDED, PaymentIntent::query()->find($intentId)->status);
        $this->assertSame(BookingLifecycle::STATUS_REFUNDED, Booking::withoutGlobalScopes()->find($bookingId)->status);
        $this->assertAuditRecorded($tenant, 'booking.payment.refunded');
        $this->assertAuditRecorded($tenant, 'payment.intent.refunded');
        $this->assertSame(
            1,
            OutboxEvent::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('event_type', 'payment.intent.refunded')->count(),
        );

        // Refund replay: ignored, one audit, one outbox fact.
        $this->sendWebhook($this->refundBody('pay_test_0001', $amount))
            ->assertOk()
            ->assertJsonPath('data.status', 'ignored')
            ->assertJsonPath('data.reason', 'replay');
        $this->assertSame(1, $this->auditCount($tenant, 'payment.intent.refunded'));
    }

    public function test_capture_after_cancellation_is_ignored_and_never_confirms(): void
    {
        [$tenant, $tenantId, $bookingId, $intentId, $orderId, $amount] = $this->setUpCheckout();

        // Customer cancels while the payment is in flight (allowed from
        // PENDING_PAYMENT) — the capture that lands afterwards must not
        // resurrect the booking.
        Booking::withoutGlobalScopes()->whereKey($bookingId)->update(['status' => BookingLifecycle::STATUS_CANCELLED]);

        $this->sendWebhook($this->capturedBody($orderId, $amount))
            ->assertOk()
            ->assertJsonPath('data.status', 'ignored')
            ->assertJsonPath('data.reason', 'state_forbidden');

        // The intent stays PENDING: the booking refused the leg, so
        // nothing that could be mistaken for a settled booking is written
        // (the refund/ops track picks the stranded capture up).
        $this->assertSame(PaymentIntent::STATUS_PENDING, PaymentIntent::query()->find($intentId)->status);
        $this->assertSame(BookingLifecycle::STATUS_CANCELLED, Booking::withoutGlobalScopes()->find($bookingId)->status);
        $this->assertSame(0, $this->auditCount($tenant, 'payment.intent.confirmed'));
        $this->assertSame(0, OutboxEvent::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('event_type', 'payment.intent.confirmed')->count());
    }

    public function test_unknown_orders_and_unsupported_events_are_ignored(): void
    {
        config(['payments.razorpay.webhook_secret' => self::SECRET]);

        $this->sendWebhook($this->capturedBody('order_ghost_1', 450000))
            ->assertOk()
            ->assertJsonPath('data.status', 'ignored')
            ->assertJsonPath('data.reason', 'unknown_order');

        $this->sendWebhook((string) json_encode(['event' => 'order.paid']))
            ->assertOk()
            ->assertJsonPath('data.status', 'ignored')
            ->assertJsonPath('data.reason', 'unsupported_event');
    }
}
