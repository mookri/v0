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
    public function createOrder(int $amountMinor, string $currency, string $receipt): ?string
    {
        return 'order_fake_'.strtolower((string) Str::ulid());
    }
}
