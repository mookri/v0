<?php

namespace App\Modules\Catalog\Contracts;

use Carbon\CarbonImmutable;

/**
 * Quote port (deptrac-blessed Contracts surface): the server-side price
 * authority behind checkout (B3) — the booking module re-quotes at
 * :confirm time and snapshots the amount, never trusting a client total.
 * QuoteCalculator's integer minor-unit rules apply (whole-quote failure
 * on unknown SKUs or currency mismatches, never partial totals).
 *
 * The result is a CLOSED shape: a failed quote carries a stable reason
 * string instead of an exception, because consumers live in other modules
 * and cannot import catalog exception classes (doc 01 §5).
 */
interface CalculatesQuotes
{
    /**
     * @param  list<array{sku: string, quantity: int, pandit_id?: string|null, market?: string|null}>  $lines
     * @return array{ok: true, lines: list<array{sku: string, quantity: int, pandit_id?: string|null, market: string, unit_minor: int, line_minor: int}>, total_minor: int, currency: string}|array{ok: false, error: string}
     */
    public function quote(string $currency, array $lines, CarbonImmutable $at): array;
}
