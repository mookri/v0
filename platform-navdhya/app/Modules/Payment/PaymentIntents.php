<?php

namespace App\Modules\Payment;

use App\Modules\Payment\Contracts\CreatesPaymentIntents;
use App\Modules\Payment\Domain\PaymentReconciliationService;
use App\Modules\Payment\Gateways\PaymentGateway;
use App\Modules\Payment\Models\PaymentIntent;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;

/**
 * Payment-intent service (B3): owns the amount snapshot — created once
 * at :confirm from the QuoteCalculator quote, never re-priced. begin()
 * opens the provider order and appends the payment.intent.created outbox
 * event inside the caller's transaction (same-tx guarantee: the fact and
 * the event commit or roll back together, doc 07 F2).
 */
final class PaymentIntents implements CreatesPaymentIntents
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly Outbox $outbox,
        private readonly AuditWriter $audit,
    ) {
    }

    public function begin(string $tenantId, string $bookingId, int $userId, int $amountMinor, string $currency, array $quote): array
    {
        // Idempotent: an existing intent is returned as-is (the unique
        // (tenant, booking) index backs this under concurrency).
        $existing = $this->intentRow($tenantId, $bookingId);
        if ($existing !== null) {
            return ['ok' => true, 'intent' => self::serialize($existing)];
        }

        $orderId = $this->gateway->createOrder($amountMinor, $currency, 'booking_'.$bookingId);
        if ($orderId === null) {
            return ['ok' => false, 'reason' => 'gateway_unavailable'];
        }

        $intent = new PaymentIntent();
        $intent->forceFill([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'status' => PaymentIntent::STATUS_PENDING,
            'provider' => (string) config('payments.driver', 'razorpay'),
            'test_mode' => (bool) config('payments.test_mode', true),
            'provider_order_id' => $orderId,
            'quote_snapshot' => $quote,
        ]);
        $intent->save();

        app(Domain\DurablePaymentService::class)->registerRoute(
            (string) config('payments.driver', 'razorpay'),
            $orderId,
            $tenantId
        );

        $this->outbox->append(
            $tenantId,
            'payment_intent',
            (string) $intent->getKey(),
            'payment.intent.created',
            [
                'intent_id' => (string) $intent->getKey(),
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
            ],
        );

        $this->audit->record(
            'payment.intent.created',
            ['module' => 'payment', 'resource_type' => 'payment_intent', 'resource_id' => (string) $intent->getKey()],
            null,
            ['status' => PaymentIntent::STATUS_PENDING, 'booking_id' => $bookingId, 'amount_minor' => $amountMinor, 'currency' => $currency],
        );

        return ['ok' => true, 'intent' => self::serialize($intent)];
    }

    public function forBooking(string $tenantId, string $bookingId): ?array
    {
        $row = $this->intentRow($tenantId, $bookingId);

        return $row === null ? null : self::serialize($row);
    }

    public function markUnknown(string $tenantId, string $bookingId, string $reason): ?array
    {
        $updated = app(PaymentReconciliationService::class)->markUnknown($tenantId, $bookingId, $reason);

        return $updated === null ? null : self::serialize($updated);
    }

    private function intentRow(string $tenantId, string $bookingId): ?PaymentIntent
    {
        return PaymentIntent::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->first();
    }

    /**
     * @return array{object: string, id: string, booking_id: string, status: string, amount_minor: int, currency: string, provider: string, provider_order_id: string, test_mode: bool}
     */
    private static function serialize(PaymentIntent $intent): array
    {
        return [
            'object' => 'payment_intent',
            'id' => (string) $intent->getKey(),
            'booking_id' => (string) $intent->booking_id,
            'status' => (string) $intent->status,
            'amount_minor' => (int) $intent->amount_minor,
            'currency' => (string) $intent->currency,
            'provider' => (string) $intent->provider,
            'provider_order_id' => (string) $intent->provider_order_id,
            'test_mode' => (bool) $intent->test_mode,
        ];
    }
}
