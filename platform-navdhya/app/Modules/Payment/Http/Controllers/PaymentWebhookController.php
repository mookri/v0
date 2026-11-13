<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Booking\Contracts\TransitionsBookingPayment;
use App\Modules\Payment\Models\PaymentIntent;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment webhook (B3): the ONLY money-confirmation path. Signature =
 * HMAC-SHA256 over the RAW request body (Razorpay's scheme) against
 * RAZORPAY_WEBHOOK_SECRET — verified before any parsing, fail-closed
 * (no secret configured → 503, never a silent accept).
 *
 * Replay- and out-of-order safety is by STATUS GUARD, not event ids:
 * every leg applies only when the intent is in the status that money
 * fact can move it out of, and the booking-side port refuses any leg the
 * lifecycle matrix doesn't allow — an ignored receipt answers 200 so the
 * provider stops retrying events we can never act on.
 *
 * Tenant: this route drops the tenant middleware, and the intent row IS
 * the tenant authority — resolved explicitly before the booking
 * transitions run under ambient context (audit chains need it).
 */
final class PaymentWebhookController
{
    public function __construct(
        private readonly TransitionsBookingPayment $bookings,
        private readonly Outbox $outbox,
        private readonly AuditWriter $audit,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $requestId = (string) $request->attributes->get('request_id', '');

        $secret = config('payments.razorpay.webhook_secret');
        if (! is_string($secret) || $secret === '') {
            return new JsonResponse([
                'error' => ['code' => 'dependency_unavailable', 'message' => 'Webhook verification is not configured.', 'request_id' => $requestId],
            ], 503);
        }

        $body = (string) $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature');
        if ($signature === '' || ! hash_equals(hash_hmac('sha256', $body, $secret), $signature)) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Invalid webhook signature.', 'request_id' => $requestId],
            ], 401);
        }

        $payload = json_decode($body, true);
        if (! is_array($payload)) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Webhook payload is not valid JSON.', 'request_id' => $requestId],
            ], 422);
        }

        $event = (string) ($payload['event'] ?? '');
        $incomingEventId = $payload['event_id'] ?? $request->header('X-Razorpay-Event-Id');

        if (is_string($incomingEventId) && $incomingEventId !== '') {
            $existingInbox = \App\Modules\Payment\Models\Central\ProviderEventInbox::query()
                ->where('provider_code', 'RAZORPAY')
                ->where('event_id', $incomingEventId)
                ->first();

            if ($existingInbox !== null) {
                return $this->receipt($request, 'ignored', null, 'replay');
            }
            $eventId = $incomingEventId;
        } else {
            $eventId = (string) \Illuminate\Support\Str::ulid();
        }

        // Record incoming verified event in inbox
        $inboxItem = new \App\Modules\Payment\Models\Central\ProviderEventInbox();
        $inboxItem->forceFill([
            'provider_code' => 'RAZORPAY',
            'event_id' => $eventId,
            'event_type' => $event,
            'raw_payload' => $body,
            'headers_json' => $request->headers->all(),
            'signature' => $signature,
            'status' => \App\Modules\Payment\Models\Central\ProviderEventInbox::STATUS_VERIFIED,
        ]);
        $inboxItem->save();

        $response = match ($event) {
            'payment.captured' => $this->captured($request, (array) ($payload['payload']['payment']['entity'] ?? [])),
            'payment.failed' => $this->failed($request, (array) ($payload['payload']['payment']['entity'] ?? [])),
            'refund.processed' => $this->refunded($request, (array) ($payload['payload']['refund']['entity'] ?? [])),
            default => $this->receipt($request, 'ignored', null, 'unsupported_event'),
        };

        $inboxItem->status = \App\Modules\Payment\Models\Central\ProviderEventInbox::STATUS_PROCESSED;
        $inboxItem->processed_at = now();
        $inboxItem->save();

        return $response;
    }

    /**
     * @param  array<string, mixed>  $entity
     */
    private function captured(Request $request, array $entity): JsonResponse
    {
        $orderId = (string) ($entity['order_id'] ?? '');
        $paymentId = (string) ($entity['id'] ?? '');

        $route = \App\Modules\Payment\Models\Central\ProviderOperationRoute::query()
            ->where('provider_code', 'RAZORPAY')
            ->where('provider_operation_id', $orderId)
            ->first();

        $intent = null;
        if ($route !== null) {
            $context = app(TenantContext::class);
            $context->clear();
            $context->set($route->tenant_id, null, null, $route->connection_name);
            $intent = PaymentIntent::query()->where('provider_order_id', $orderId)->first();
        }

        if ($intent === null && $orderId !== '') {
            $intent = PaymentIntent::query()
                ->where('provider_order_id', $orderId)
                ->first();
        }

        if ($intent === null) {
            return $this->receipt($request, 'ignored', null, 'unknown_order');
        }

        if ($paymentId !== '') {
            app(\App\Modules\Payment\Domain\DurablePaymentService::class)->registerRoute(
                'RAZORPAY',
                $paymentId,
                (string) $intent->tenant_id,
                $intent->getConnectionName()
            );
        }

        // Money guard: only the exact snapshot may ever be confirmed —
        // a mismatched amount or currency is an incident, never a
        // confirmation (and never a retry: it will not fix itself).
        if ((int) ($entity['amount'] ?? -1) !== (int) $intent->amount_minor
            || (string) ($entity['currency'] ?? '') !== (string) $intent->currency) {
            Log::error('payment.webhook.money_mismatch', [
                'intent_id' => (string) $intent->getKey(),
                'event_amount' => $entity['amount'] ?? null,
                'event_currency' => $entity['currency'] ?? null,
                'snapshot_minor' => (int) $intent->amount_minor,
                'snapshot_currency' => (string) $intent->currency,
            ]);

            return $this->receipt($request, 'ignored', $intent, 'money_mismatch');
        }

        if (! in_array((string) $intent->status, [PaymentIntent::STATUS_PENDING, PaymentIntent::STATUS_PAYMENT_UNKNOWN], true)) {
            return $this->receipt($request, 'ignored', $intent, 'replay');
        }

        return $this->apply(
            $request,
            $intent,
            PaymentIntent::STATUS_SUCCEEDED,
            'payment.intent.confirmed',
            fn (string $bookingId): array => $this->bookings->paymentConfirmed($bookingId),
            array_filter([
                'provider_payment_id' => $paymentId !== '' ? $paymentId : null,
                'reconciled_at' => now(),
                'last_reconcile_error' => null,
            ], static fn ($v, $k): bool => $k === 'last_reconcile_error' || $v !== null, ARRAY_FILTER_USE_BOTH),
        );
    }

    /**
     * @param  array<string, mixed>  $entity
     */
    private function failed(Request $request, array $entity): JsonResponse
    {
        $orderId = (string) ($entity['order_id'] ?? '');

        $route = \App\Modules\Payment\Models\Central\ProviderOperationRoute::query()
            ->where('provider_code', 'RAZORPAY')
            ->where('provider_operation_id', $orderId)
            ->first();

        $intent = null;
        if ($route !== null) {
            $context = app(TenantContext::class);
            $context->clear();
            $context->set($route->tenant_id, null, null, $route->connection_name);
            $intent = PaymentIntent::query()->where('provider_order_id', $orderId)->first();
        }

        if ($intent === null && $orderId !== '') {
            $intent = PaymentIntent::query()
                ->where('provider_order_id', $orderId)
                ->first();
        }

        if ($intent === null) {
            return $this->receipt($request, 'ignored', null, 'unknown_order');
        }

        if (! in_array((string) $intent->status, [PaymentIntent::STATUS_PENDING, PaymentIntent::STATUS_PAYMENT_UNKNOWN], true)) {
            return $this->receipt($request, 'ignored', $intent, 'replay');
        }

        return $this->apply(
            $request,
            $intent,
            PaymentIntent::STATUS_FAILED,
            'payment.intent.failed',
            fn (string $bookingId): array => $this->bookings->paymentFailed($bookingId),
            ['reconciled_at' => now()],
        );
    }

    /**
     * @param  array<string, mixed>  $entity
     */
    private function refunded(Request $request, array $entity): JsonResponse
    {
        $paymentId = (string) ($entity['payment_id'] ?? '');

        $route = \App\Modules\Payment\Models\Central\ProviderOperationRoute::query()
            ->where('provider_code', 'RAZORPAY')
            ->where('provider_operation_id', $paymentId)
            ->first();

        $intent = null;
        if ($route !== null) {
            $context = app(TenantContext::class);
            $context->clear();
            $context->set($route->tenant_id, null, null, $route->connection_name);
            $intent = PaymentIntent::query()->where('provider_payment_id', $paymentId)->first();
        }

        if ($intent === null && $paymentId !== '') {
            $intent = PaymentIntent::query()
                ->where('provider_payment_id', $paymentId)
                ->first();
        }

        if ($intent === null) {
            return $this->receipt($request, 'ignored', null, 'unknown_payment');
        }

        if ((string) $intent->status !== PaymentIntent::STATUS_SUCCEEDED) {
            return $this->receipt($request, 'ignored', $intent, 'replay');
        }

        // Full refunds only in this leg (partial-refund accounting lands
        // with the refunds track — a partial amount never settles here).
        if ((int) ($entity['amount'] ?? -1) !== (int) $intent->amount_minor) {
            Log::warning('payment.webhook.partial_refund_unsupported', [
                'intent_id' => (string) $intent->getKey(),
                'event_amount' => $entity['amount'] ?? null,
                'snapshot_minor' => (int) $intent->amount_minor,
            ]);

            return $this->receipt($request, 'ignored', $intent, 'partial_refund');
        }

        return $this->apply(
            $request,
            $intent,
            PaymentIntent::STATUS_REFUNDED,
            'payment.intent.refunded',
            fn (string $bookingId): array => $this->bookings->paymentRefunded($bookingId),
        );
    }

    /**
     * One transaction per leg: booking transition (payment module calls
     * the booking port — never booking rows) + intent status + outbox
     * event + audit. The port refusing the leg (replay on the booking
     * side, cancelled meanwhile, unknown booking) writes NOTHING and
     * returns an ignored receipt.
     *
     * @param  \Closure(string): array<string, mixed>  $outcome
     * @param  array<string, mixed>  $intentPatch
     */
    private function apply(Request $request, PaymentIntent $intent, string $toStatus, string $eventType, \Closure $outcome, array $intentPatch = []): JsonResponse
    {
        $context = app(TenantContext::class);
        $context->clear();
        $context->set((string) $intent->tenant_id, null);

        try {
            $applied = DB::connection($intent->getConnectionName() ?: (string) config('database.default'))->transaction(function () use ($intent, $toStatus, $eventType, $outcome, $intentPatch): bool {
                $booking = ($outcome)((string) $intent->booking_id);

                if (! ($booking['ok'] ?? false)) {
                    return false;
                }

                $fromStatus = (string) $intent->status;
                $intent->forceFill(['status' => $toStatus, ...$intentPatch])->save();

                $opType = match ($toStatus) {
                    PaymentIntent::STATUS_SUCCEEDED => \App\Modules\Payment\Models\PaymentOperation::TYPE_CAPTURE,
                    PaymentIntent::STATUS_FAILED => \App\Modules\Payment\Models\PaymentOperation::TYPE_AUTHORIZE,
                    PaymentIntent::STATUS_REFUNDED => \App\Modules\Payment\Models\PaymentOperation::TYPE_REFUND,
                    default => 'UNKNOWN',
                };

                $operation = new \App\Modules\Payment\Models\PaymentOperation();
                $operation->forceFill([
                    'tenant_id' => (string) $intent->tenant_id,
                    'booking_id' => (string) $intent->booking_id,
                    'operation_type' => $opType,
                    'amount_minor' => (int) $intent->amount_minor,
                    'currency' => (string) $intent->currency,
                    'status' => $toStatus === PaymentIntent::STATUS_FAILED
                        ? \App\Modules\Payment\Models\PaymentOperation::STATUS_FAILED
                        : \App\Modules\Payment\Models\PaymentOperation::STATUS_SUCCEEDED,
                    'provider_code' => 'RAZORPAY',
                    'provider_operation_id' => $intentPatch['provider_payment_id'] ?? $intent->provider_payment_id ?? $intent->provider_order_id,
                ]);
                $operation->save();

                $this->outbox->append(
                    (string) $intent->tenant_id,
                    'payment_intent',
                    (string) $intent->getKey(),
                    $eventType,
                    [
                        'intent_id' => (string) $intent->getKey(),
                        'booking_id' => (string) $intent->booking_id,
                        'amount_minor' => (int) $intent->amount_minor,
                        'currency' => (string) $intent->currency,
                        'booking_from' => $booking['from'] ?? null,
                        'booking_to' => $booking['to'] ?? null,
                    ],
                );

                $this->audit->record(
                    $eventType,
                    ['module' => 'payment', 'resource_type' => 'payment_intent', 'resource_id' => (string) $intent->getKey()],
                    ['status' => $fromStatus],
                    ['status' => $toStatus],
                );

                return true;
            });
        } finally {
            $context->clear();
        }

        return $applied
            ? $this->receipt($request, 'processed', $intent, null)
            : $this->receipt($request, 'ignored', $intent, 'state_forbidden');
    }

    private function receipt(Request $request, string $status, ?PaymentIntent $intent, ?string $reason): JsonResponse
    {
        return new JsonResponse([
            'data' => [
                'object' => 'webhook_receipt',
                'status' => $status,
                'intent_id' => $intent === null ? null : (string) $intent->getKey(),
                'reason' => $reason,
            ],
            'meta' => ['request_id' => (string) $request->attributes->get('request_id', '')],
        ]);
    }
}
