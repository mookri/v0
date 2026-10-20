<?php

namespace App\Modules\Payment\Contracts;

/**
 * Payment-intent port (deptrac-blessed Contracts surface, consumed by
 * the booking module's :confirm): begin checkout by snapshotting the
 * server-computed quote amount into an intent and opening a provider
 * order. Closed result shapes — gateway failures are reasons, never
 * exceptions across the module boundary.
 *
 * Idempotency: one intent per (tenant, booking), enforced by a unique
 * index — begin() returns the existing intent as-is. Callers serialize
 * concurrent begins by locking the booking row first, so a racing pair
 * can never open two provider orders.
 */
interface CreatesPaymentIntents
{
    /**
     * @param  array{ok: true, lines: list<array<string, mixed>>, total_minor: int, currency: string}  $quote  result of the catalog CalculatesQuotes port
     * @return array{ok: true, intent: array{object: string, id: string, booking_id: string, status: string, amount_minor: int, currency: string, provider: string, provider_order_id: string, test_mode: bool}}|array{ok: false, reason: string}
     */
    public function begin(string $tenantId, string $bookingId, int $userId, int $amountMinor, string $currency, array $quote): array;

    /**
     * The booking's intent in any status, or null when checkout never began.
     *
     * @return array{object: string, id: string, booking_id: string, status: string, amount_minor: int, currency: string, provider: string, provider_order_id: string, test_mode: bool}|null
     */
    public function forBooking(string $tenantId, string $bookingId): ?array;
}
