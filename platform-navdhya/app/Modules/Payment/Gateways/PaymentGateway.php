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
}
