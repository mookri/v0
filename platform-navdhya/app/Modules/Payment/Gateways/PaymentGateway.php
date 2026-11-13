<?php

namespace App\Modules\Payment\Gateways;

/**
 * Provider gateway port (module-internal — never imported across module
 * lines; cross-module callers use Payment\Contracts\CreatesPaymentIntents).
 * Fail-closed by contract: an unconfigured or unreachable provider
 * returns null, never an invented or zero-amount order.
 */
interface PaymentGateway
{
    /**
     * Open a provider order for the snapshot amount (minor units).
     * Returns the provider order id, or null on any failure.
     */
    public function createOrder(int $amountMinor, string $currency, string $receipt): ?string;

    /**
     * Poll the provider for the authoritative state of an existing order id.
     *
     * @return array{status: 'PENDING'|'PAID'|'FAILED'|'UNKNOWN', provider_payment_id?: ?string, amount_minor?: int, currency?: string, error?: ?string}
     */
    public function fetchOrderStatus(string $orderId): array;

    /**
     * Poll the provider by client receipt reference when an order creation
     * response timed out or failed ambiguously before an order id was persisted.
     *
     * @return array{status: 'PENDING'|'PAID'|'FAILED'|'UNKNOWN', provider_order_id?: ?string, provider_payment_id?: ?string, amount_minor?: int, currency?: string, error?: ?string}
     */
    public function findOrderByReceipt(string $receipt): array;
}
