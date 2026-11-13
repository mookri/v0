<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain;

use App\Modules\Booking\Contracts\TransitionsBookingPayment;
use App\Modules\Payment\Contracts\HandlesUncertainPaymentCompensation;
use App\Modules\Payment\Gateways\PaymentGateway;
use App\Modules\Payment\Models\PaymentIntent;
use App\Modules\Payment\Models\PaymentOperation;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Uncertain-payment reconciler (T-AP02-02): resolves intents stuck in PAYMENT_UNKNOWN
 * by querying the gateway, driving captured/failed transitions through
 * TransitionsBookingPayment, and emitting durable refund compensation when a hold has
 * already expired or been cancelled before payment settlement was observed.
 */
final class PaymentReconciliationService
{
    public const PENDING_RECEIPT_PREFIX = 'receipt:';

    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly TransitionsBookingPayment $bookings,
        private readonly Outbox $outbox,
        private readonly AuditWriter $audit,
    ) {
    }

    public function markUnknown(
        string $tenantId,
        string $bookingId,
        string $reason,
        ?string $providerOrderId = null,
    ): ?PaymentIntent {
        $intent = PaymentIntent::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->first();

        if ($intent === null) {
            return null;
        }

        if (in_array((string) $intent->status, [PaymentIntent::STATUS_SUCCEEDED, PaymentIntent::STATUS_REFUNDED], true)) {
            return $intent;
        }

        $previousStatus = (string) $intent->status;
        $patch = [
            'status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN,
            'last_reconcile_error' => mb_substr($reason, 0, 255),
        ];
        if (is_string($providerOrderId) && $providerOrderId !== '') {
            $patch['provider_order_id'] = $providerOrderId;
        }
        $intent->forceFill($patch)->save();

        $this->outbox->append(
            $tenantId,
            'payment_intent',
            (string) $intent->getKey(),
            'payment.intent.unknown',
            [
                'intent_id' => (string) $intent->getKey(),
                'booking_id' => $bookingId,
                'previous_status' => $previousStatus,
                'reason' => $reason,
            ]
        );

        $this->audit->record(
            'payment.intent.unknown',
            ['module' => 'payment', 'resource_type' => 'payment_intent', 'resource_id' => (string) $intent->getKey()],
            ['status' => $previousStatus],
            ['status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN, 'reason' => $reason]
        );

        return $intent;
    }

    /**
     * Record an intent directly in PAYMENT_UNKNOWN when checkout gateway invocation
     * ended with an ambiguous outcome (timeout / 5xx) where a provider order may exist.
     *
     * @param  array<string, mixed>  $quote
     */
    public function recordAmbiguousCheckout(
        string $tenantId,
        string $bookingId,
        int $userId,
        int $amountMinor,
        string $currency,
        array $quote,
        string $reason,
    ): PaymentIntent {
        $existing = PaymentIntent::query()
            ->where('tenant_id', $tenantId)
            ->where('booking_id', $bookingId)
            ->first();

        if ($existing !== null) {
            return $this->markUnknown($tenantId, $bookingId, $reason) ?? $existing;
        }

        $intent = new PaymentIntent();
        $intent->forceFill([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN,
            'provider' => (string) config('payments.driver', 'razorpay'),
            'test_mode' => (bool) config('payments.test_mode', true),
            'provider_order_id' => self::PENDING_RECEIPT_PREFIX.'booking_'.$bookingId,
            'quote_snapshot' => $quote,
            'last_reconcile_error' => mb_substr($reason, 0, 255),
        ]);
        $intent->save();

        $this->outbox->append(
            $tenantId,
            'payment_intent',
            (string) $intent->getKey(),
            'payment.intent.unknown',
            [
                'intent_id' => (string) $intent->getKey(),
                'booking_id' => $bookingId,
                'reason' => $reason,
            ]
        );

        $this->audit->record(
            'payment.intent.unknown',
            ['module' => 'payment', 'resource_type' => 'payment_intent', 'resource_id' => (string) $intent->getKey()],
            null,
            ['status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN, 'reason' => $reason]
        );

        return $intent;
    }

    /**
     * @return array{checked: int, confirmed: int, compensated: int, failed: int, deferred: int}
     */
    public function reconcileAllUnknown(?string $tenantId = null): array
    {
        $intents = PaymentIntent::query()
            ->where('status', PaymentIntent::STATUS_PAYMENT_UNKNOWN)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('updated_at')
            ->get();

        $summary = [
            'checked' => 0,
            'confirmed' => 0,
            'compensated' => 0,
            'failed' => 0,
            'deferred' => 0,
        ];

        foreach ($intents as $intent) {
            $summary['checked']++;
            $outcome = $this->reconcileOne($intent);
            if (array_key_exists($outcome, $summary)) {
                $summary[$outcome]++;
            }
        }

        return $summary;
    }

    /**
     * @return 'confirmed'|'compensated'|'failed'|'deferred'|'skipped'
     */
    public function reconcileOne(PaymentIntent $intent): string
    {
        if ((string) $intent->status !== PaymentIntent::STATUS_PAYMENT_UNKNOWN) {
            return 'skipped';
        }

        $orderId = (string) $intent->provider_order_id;
        $lookup = str_starts_with($orderId, self::PENDING_RECEIPT_PREFIX)
            ? $this->gateway->findOrderByReceipt(substr($orderId, strlen(self::PENDING_RECEIPT_PREFIX)))
            : $this->gateway->fetchOrderStatus($orderId);

        $status = strtoupper((string) ($lookup['status'] ?? 'UNKNOWN'));
        $discoveredOrderId = is_string($lookup['provider_order_id'] ?? null) && $lookup['provider_order_id'] !== ''
            ? $lookup['provider_order_id']
            : null;
        $paymentId = is_string($lookup['provider_payment_id'] ?? null) && $lookup['provider_payment_id'] !== ''
            ? $lookup['provider_payment_id']
            : null;

        if ($status === 'PAID') {
            if (isset($lookup['amount_minor']) && (int) $lookup['amount_minor'] !== (int) $intent->amount_minor) {
                $intent->forceFill([
                    'reconcile_attempts' => ((int) $intent->reconcile_attempts) + 1,
                    'last_reconcile_error' => 'money_mismatch:gateway_amount_'.$lookup['amount_minor'],
                ])->save();

                return 'deferred';
            }

            if (isset($lookup['currency']) && strtoupper((string) $lookup['currency']) !== strtoupper((string) $intent->currency)) {
                $intent->forceFill([
                    'reconcile_attempts' => ((int) $intent->reconcile_attempts) + 1,
                    'last_reconcile_error' => 'currency_mismatch:'.(string) $lookup['currency'],
                ])->save();

                return 'deferred';
            }

            return $this->settlePaid($intent, $discoveredOrderId, $paymentId);
        }

        if ($status === 'FAILED') {
            return $this->settleFailed($intent, $discoveredOrderId, (string) ($lookup['error'] ?? 'gateway_failed'));
        }

        $intent->forceFill([
            'provider_order_id' => $discoveredOrderId ?? $intent->provider_order_id,
            'reconcile_attempts' => ((int) $intent->reconcile_attempts) + 1,
            'last_reconcile_error' => is_string($lookup['error'] ?? null) ? mb_substr($lookup['error'], 0, 255) : 'pending_at_gateway',
        ])->save();

        if ((int) $intent->reconcile_attempts >= 5) {
            Log::warning('payment.reconcile.exceeded_retry_budget', [
                'intent_id' => (string) $intent->getKey(),
                'booking_id' => (string) $intent->booking_id,
                'attempts' => (int) $intent->reconcile_attempts,
            ]);
        }

        return 'deferred';
    }

    /**
     * @return 'confirmed'|'compensated'
     */
    private function settlePaid(PaymentIntent $intent, ?string $discoveredOrderId, ?string $paymentId): string
    {
        $context = app(TenantContext::class);
        $context->clear();
        $tenantId = (string) $intent->tenant_id;
        $tenant = Tenant::query()->find($tenantId);
        $connection = $tenant !== null
            ? (new ConnectionResolver())->cachedFor($tenant)
            : ($intent->getConnectionName() ?: (string) config('database.default'));

        $context->set($tenantId, null, null, $connection);

        try {
            $outcome = DB::connection($connection)->transaction(function () use ($intent, $tenantId, $discoveredOrderId, $paymentId): string {
                $now = CarbonImmutable::now('UTC');
                $bookingResult = $this->bookings->paymentConfirmed((string) $intent->booking_id);

                $intentPatch = [
                    'status' => PaymentIntent::STATUS_SUCCEEDED,
                    'reconciled_at' => $now,
                    'reconcile_attempts' => ((int) $intent->reconcile_attempts) + 1,
                ];
                if ($discoveredOrderId !== null) {
                    $intentPatch['provider_order_id'] = $discoveredOrderId;
                }
                if ($paymentId !== null) {
                    $intentPatch['provider_payment_id'] = $paymentId;
                }

                if ($bookingResult['ok'] ?? false) {
                    $intentPatch['last_reconcile_error'] = null;
                    $intent->forceFill($intentPatch)->save();

                    $operation = new PaymentOperation();
                    $operation->forceFill([
                        'tenant_id' => $tenantId,
                        'booking_id' => (string) $intent->booking_id,
                        'operation_type' => PaymentOperation::TYPE_CAPTURE,
                        'amount_minor' => (int) $intent->amount_minor,
                        'currency' => (string) $intent->currency,
                        'status' => PaymentOperation::STATUS_SUCCEEDED,
                        'provider_code' => strtoupper((string) $intent->provider),
                        'provider_operation_id' => $paymentId ?? (string) $intent->provider_order_id,
                    ]);
                    $operation->save();

                    $this->outbox->append(
                        $tenantId,
                        'payment_intent',
                        (string) $intent->getKey(),
                        'payment.intent.confirmed',
                        [
                            'intent_id' => (string) $intent->getKey(),
                            'booking_id' => (string) $intent->booking_id,
                            'amount_minor' => (int) $intent->amount_minor,
                            'currency' => (string) $intent->currency,
                            'reconciled' => true,
                        ]
                    );

                    $this->audit->record(
                        'payment.intent.confirmed',
                        ['module' => 'payment', 'resource_type' => 'payment_intent', 'resource_id' => (string) $intent->getKey()],
                        ['status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN],
                        ['status' => PaymentIntent::STATUS_SUCCEEDED, 'reconciled' => true]
                    );

                    return 'confirmed';
                }

                // Captured payment arrived after booking hold expired or was cancelled:
                // record capture truth, enqueue durable refund compensation, never silent-drop money.
                $reason = 'booking_unfulfillable:'.(string) ($bookingResult['from'] ?? ($bookingResult['reason'] ?? 'unknown'));
                $intentPatch['last_reconcile_error'] = mb_substr($reason, 0, 255);
                $intent->forceFill($intentPatch)->save();

                $refundOp = new PaymentOperation();
                $refundOp->forceFill([
                    'tenant_id' => $tenantId,
                    'booking_id' => (string) $intent->booking_id,
                    'operation_type' => PaymentOperation::TYPE_REFUND,
                    'amount_minor' => (int) $intent->amount_minor,
                    'currency' => (string) $intent->currency,
                    'status' => PaymentOperation::STATUS_PENDING,
                    'provider_code' => strtoupper((string) $intent->provider),
                    'provider_operation_id' => $paymentId ?? (string) $intent->provider_order_id,
                    'error_code' => 'booking_unfulfillable',
                    'error_message' => $reason,
                ]);
                $refundOp->save();

                $payload = [
                    'tenant_id' => $tenantId,
                    'booking_id' => (string) $intent->booking_id,
                    'intent_id' => (string) $intent->getKey(),
                    'amount_minor' => (int) $intent->amount_minor,
                    'currency' => (string) $intent->currency,
                    'provider_payment_id' => $paymentId,
                    'reason' => $reason,
                ];

                $this->outbox->append(
                    $tenantId,
                    'payment_intent',
                    (string) $intent->getKey(),
                    'payment.reconciliation.compensation_required',
                    $payload
                );

                $this->audit->record(
                    'payment.reconciliation.compensation_required',
                    ['module' => 'payment', 'resource_type' => 'payment_intent', 'resource_id' => (string) $intent->getKey()],
                    ['status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN],
                    ['status' => PaymentIntent::STATUS_SUCCEEDED, 'reason' => $reason, 'refund_operation_id' => (string) $refundOp->getKey()]
                );

                if (app()->bound(HandlesUncertainPaymentCompensation::class)) {
                    app(HandlesUncertainPaymentCompensation::class)->compensateCapturedUnfulfillableBooking($payload);
                }

                return 'compensated';
            });

            return $outcome;
        } finally {
            $context->clear();
        }
    }

    /**
     * @return 'failed'
     */
    private function settleFailed(PaymentIntent $intent, ?string $discoveredOrderId, string $errorReason): string
    {
        $context = app(TenantContext::class);
        $context->clear();
        $tenantId = (string) $intent->tenant_id;
        $tenant = Tenant::query()->find($tenantId);
        $connection = $tenant !== null
            ? (new ConnectionResolver())->cachedFor($tenant)
            : ($intent->getConnectionName() ?: (string) config('database.default'));

        $context->set($tenantId, null, null, $connection);

        try {
            DB::connection($connection)->transaction(function () use ($intent, $tenantId, $discoveredOrderId, $errorReason): void {
                $bookingResult = $this->bookings->paymentFailed((string) $intent->booking_id);

                $intent->forceFill([
                    'status' => PaymentIntent::STATUS_FAILED,
                    'provider_order_id' => $discoveredOrderId ?? $intent->provider_order_id,
                    'reconciled_at' => CarbonImmutable::now('UTC'),
                    'reconcile_attempts' => ((int) $intent->reconcile_attempts) + 1,
                    'last_reconcile_error' => mb_substr($errorReason, 0, 255),
                ])->save();

                $operation = new PaymentOperation();
                $operation->forceFill([
                    'tenant_id' => $tenantId,
                    'booking_id' => (string) $intent->booking_id,
                    'operation_type' => PaymentOperation::TYPE_AUTHORIZE,
                    'amount_minor' => (int) $intent->amount_minor,
                    'currency' => (string) $intent->currency,
                    'status' => PaymentOperation::STATUS_FAILED,
                    'provider_code' => strtoupper((string) $intent->provider),
                    'provider_operation_id' => $discoveredOrderId ?? (string) $intent->provider_order_id,
                    'error_code' => 'reconciled_failed',
                    'error_message' => $errorReason,
                ]);
                $operation->save();

                $this->outbox->append(
                    $tenantId,
                    'payment_intent',
                    (string) $intent->getKey(),
                    'payment.intent.failed',
                    [
                        'intent_id' => (string) $intent->getKey(),
                        'booking_id' => (string) $intent->booking_id,
                        'amount_minor' => (int) $intent->amount_minor,
                        'currency' => (string) $intent->currency,
                        'booking_transitioned' => (bool) ($bookingResult['ok'] ?? false),
                        'reconciled' => true,
                    ]
                );

                $this->audit->record(
                    'payment.intent.failed',
                    ['module' => 'payment', 'resource_type' => 'payment_intent', 'resource_id' => (string) $intent->getKey()],
                    ['status' => PaymentIntent::STATUS_PAYMENT_UNKNOWN],
                    ['status' => PaymentIntent::STATUS_FAILED, 'reason' => $errorReason]
                );
            });

            return 'failed';
        } finally {
            $context->clear();
        }
    }
}

