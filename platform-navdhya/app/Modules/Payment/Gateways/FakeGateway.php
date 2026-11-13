<?php

namespace App\Modules\Payment\Gateways;

use Illuminate\Support\Str;

/**
 * Offline gateway (tests, E2E, local dev): mints deterministic
 * order_fake_* ids with no network. The webhook still verifies real
 * HMAC signatures against the configured secret — only the order
 * creation leg is stubbed, never the money verification path.
 */
final class FakeGateway implements PaymentGateway
{
    /** @var array<string, array{status: 'PENDING'|'PAID'|'FAILED'|'UNKNOWN', provider_payment_id?: ?string, amount_minor?: int, currency?: string, error?: ?string}> */
    private static array $orderOutcomes = [];

    /** @var array<string, array{status: 'PENDING'|'PAID'|'FAILED'|'UNKNOWN', provider_order_id?: ?string, provider_payment_id?: ?string, amount_minor?: int, currency?: string, error?: ?string}> */
    private static array $receiptOutcomes = [];

    /**
     * @param  array{status: 'PENDING'|'PAID'|'FAILED'|'UNKNOWN', provider_payment_id?: ?string, amount_minor?: int, currency?: string, error?: ?string}  $outcome
     */
    public static function setOrderOutcome(string $orderId, array $outcome): void
    {
        self::$orderOutcomes[$orderId] = $outcome;
    }

    /**
     * @param  array{status: 'PENDING'|'PAID'|'FAILED'|'UNKNOWN', provider_order_id?: ?string, provider_payment_id?: ?string, amount_minor?: int, currency?: string, error?: ?string}  $outcome
     */
    public static function setReceiptOutcome(string $receipt, array $outcome): void
    {
        self::$receiptOutcomes[$receipt] = $outcome;
    }

    public static function resetTestOutcomes(): void
    {
        self::$orderOutcomes = [];
        self::$receiptOutcomes = [];
    }

    public function createOrder(int $amountMinor, string $currency, string $receipt): string
    {
        return 'order_fake_'.strtolower((string) Str::ulid());
    }

    public function fetchOrderStatus(string $orderId): array
    {
        return self::$orderOutcomes[$orderId] ?? ['status' => 'PENDING'];
    }

    public function findOrderByReceipt(string $receipt): array
    {
        return self::$receiptOutcomes[$receipt] ?? ['status' => 'PENDING'];
    }
}
