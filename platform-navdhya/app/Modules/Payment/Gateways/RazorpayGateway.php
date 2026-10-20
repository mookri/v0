<?php

namespace App\Modules\Payment\Gateways;

use Illuminate\Support\Facades\Http;

/**
 * Razorpay gateway (test-mode by default — PAYMENTS_TEST_MODE, doc 14
 * finance): creates orders over the Orders API with basic auth. No SDK
 * dependency; every failure path returns null (fail-closed — the caller
 * surfaces dependency_unavailable and the booking stays DRAFT).
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
        if (! is_string($this->keyId) || $this->keyId === '' || ! is_string($this->keySecret) || $this->keySecret === '') {
            return null; // unconfigured: fail-closed, no invented orders
        }

        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
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
}
