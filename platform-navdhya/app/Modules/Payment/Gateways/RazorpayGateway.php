<?php

namespace App\Modules\Payment\Gateways;

use Illuminate\Support\Facades\Http;

/**
 * Razorpay gateway (test-mode by default — PAYMENTS_TEST_MODE, doc 14
 * finance): creates and polls orders over the Orders API with basic auth.
 * Fail-closed: missing credentials or unreachable upstream returns null / UNKNOWN.
 */
final class RazorpayGateway implements PaymentGateway
{
    public function __construct(
        private readonly ?string $keyId,
        private readonly ?string $keySecret,
        private readonly string $apiBase = 'https://api.razorpay.com/v1',
    ) {
    }

    public function createOrder(int $amountMinor, string $currency, string $receipt): ?string
    {
        if (! $this->isConfigured()) {
            return null; // unconfigured: fail-closed, no invented orders
        }

        try {
            $response = Http::withBasicAuth((string) $this->keyId, (string) $this->keySecret)
                ->acceptJson()
                ->post($this->apiBase.'/orders', [
                    'amount' => $amountMinor,
                    'currency' => $currency,
                    'receipt' => $receipt,
                    'partial_payment' => false,
                ]);
        } catch (\Throwable) {
            return null; // network failure: fail-closed
        }

        if (! $response->successful()) {
            return null;
        }

        $orderId = $response->json('id');

        return is_string($orderId) && $orderId !== '' ? $orderId : null;
    }

    public function fetchOrderStatus(string $orderId): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'UNKNOWN', 'error' => 'gateway_unconfigured'];
        }

        if ($orderId === '') {
            return ['status' => 'UNKNOWN', 'error' => 'missing_order_id'];
        }

        try {
            $orderResp = Http::withBasicAuth((string) $this->keyId, (string) $this->keySecret)
                ->acceptJson()
                ->get($this->apiBase.'/orders/'.urlencode($orderId));

            if (! $orderResp->successful()) {
                return ['status' => 'UNKNOWN', 'error' => 'http_'.$orderResp->status()];
            }

            $order = $orderResp->json();
            if (! is_array($order)) {
                return ['status' => 'UNKNOWN', 'error' => 'invalid_order_payload'];
            }

            return $this->mapOrderWithPayments($order);
        } catch (\Throwable $e) {
            return ['status' => 'UNKNOWN', 'error' => $e->getMessage()];
        }
    }

    public function findOrderByReceipt(string $receipt): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'UNKNOWN', 'error' => 'gateway_unconfigured'];
        }

        if ($receipt === '') {
            return ['status' => 'UNKNOWN', 'error' => 'missing_receipt'];
        }

        try {
            $resp = Http::withBasicAuth((string) $this->keyId, (string) $this->keySecret)
                ->acceptJson()
                ->get($this->apiBase.'/orders', ['receipt' => $receipt, 'count' => 1]);

            if (! $resp->successful()) {
                return ['status' => 'UNKNOWN', 'error' => 'http_'.$resp->status()];
            }

            $items = $resp->json('items');
            if (! is_array($items) || count($items) === 0 || ! is_array($items[0])) {
                return ['status' => 'FAILED', 'error' => 'order_not_found_at_provider'];
            }

            $mapped = $this->mapOrderWithPayments($items[0]);
            $orderId = $items[0]['id'] ?? null;
            if (is_string($orderId) && $orderId !== '') {
                $mapped['provider_order_id'] = $orderId;
            }

            return $mapped;
        } catch (\Throwable $e) {
            return ['status' => 'UNKNOWN', 'error' => $e->getMessage()];
        }
    }

    private function isConfigured(): bool
    {
        return is_string($this->keyId) && $this->keyId !== ''
            && is_string($this->keySecret) && $this->keySecret !== '';
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array{status: 'PENDING'|'PAID'|'FAILED'|'UNKNOWN', provider_payment_id?: ?string, amount_minor?: int, currency?: string, error?: ?string}
     */
    private function mapOrderWithPayments(array $order): array
    {
        $razorpayStatus = strtolower((string) ($order['status'] ?? ''));
        $orderId = (string) ($order['id'] ?? '');
        $amountMinor = isset($order['amount']) ? (int) $order['amount'] : null;
        $currency = isset($order['currency']) ? (string) $order['currency'] : null;

        if ($razorpayStatus === 'paid') {
            $paymentId = $this->resolveCapturedPaymentId($orderId);

            return array_filter([
                'status' => 'PAID',
                'provider_payment_id' => $paymentId,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
            ], static fn ($v): bool => $v !== null);
        }

        if ($razorpayStatus === 'attempted') {
            return $this->checkPaymentsForFailureOrPending($orderId, $amountMinor, $currency);
        }

        if ($razorpayStatus === 'created') {
            return array_filter([
                'status' => 'PENDING',
                'amount_minor' => $amountMinor,
                'currency' => $currency,
            ], static fn ($v): bool => $v !== null);
        }

        return ['status' => 'UNKNOWN', 'error' => 'unrecognized_order_status:'.$razorpayStatus];
    }

    private function resolveCapturedPaymentId(string $orderId): ?string
    {
        if ($orderId === '') {
            return null;
        }

        try {
            $paymentsResp = Http::withBasicAuth((string) $this->keyId, (string) $this->keySecret)
                ->acceptJson()
                ->get($this->apiBase.'/orders/'.urlencode($orderId).'/payments');

            if (! $paymentsResp->successful()) {
                return null;
            }

            $items = $paymentsResp->json('items');
            if (! is_array($items)) {
                return null;
            }

            foreach ($items as $payment) {
                if (is_array($payment) && ($payment['status'] ?? null) === 'captured' && is_string($payment['id'] ?? null)) {
                    return $payment['id'];
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array{status: 'PENDING'|'PAID'|'FAILED'|'UNKNOWN', provider_payment_id?: ?string, amount_minor?: int, currency?: string, error?: ?string}
     */
    private function checkPaymentsForFailureOrPending(string $orderId, ?int $amountMinor, ?string $currency): array
    {
        if ($orderId === '') {
            return ['status' => 'PENDING'];
        }

        try {
            $paymentsResp = Http::withBasicAuth((string) $this->keyId, (string) $this->keySecret)
                ->acceptJson()
                ->get($this->apiBase.'/orders/'.urlencode($orderId).'/payments');

            if (! $paymentsResp->successful()) {
                return ['status' => 'UNKNOWN', 'error' => 'payments_lookup_http_'.$paymentsResp->status()];
            }

            $items = $paymentsResp->json('items');
            if (! is_array($items) || count($items) === 0) {
                return ['status' => 'PENDING'];
            }

            $hasPending = false;
            foreach ($items as $payment) {
                if (! is_array($payment)) {
                    continue;
                }
                $pStatus = strtolower((string) ($payment['status'] ?? ''));
                if ($pStatus === 'captured') {
                    return array_filter([
                        'status' => 'PAID',
                        'provider_payment_id' => is_string($payment['id'] ?? null) ? $payment['id'] : null,
                        'amount_minor' => $amountMinor,
                        'currency' => $currency,
                    ], static fn ($v): bool => $v !== null);
                }
                if (in_array($pStatus, ['created', 'authorized'], true)) {
                    $hasPending = true;
                }
            }

            return ['status' => $hasPending ? 'PENDING' : 'FAILED'];
        } catch (\Throwable $e) {
            return ['status' => 'UNKNOWN', 'error' => $e->getMessage()];
        }
    }
}
