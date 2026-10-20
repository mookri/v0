<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Contracts\CalculatesQuotes;
use Carbon\CarbonImmutable;

/**
 * Quote port implementation: QuoteCalculator with its exceptions folded
 * into the closed result shape (see CalculatesQuotes — cross-module
 * callers must never import catalog exception classes).
 */
final class CatalogQuotes implements CalculatesQuotes
{
    public function __construct(private readonly QuoteCalculator $calculator)
    {
    }

    public function quote(string $currency, array $lines, CarbonImmutable $at): array
    {
        try {
            $result = $this->calculator->quote($currency, $lines, $at);
        } catch (QuoteFailedException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        return ['ok' => true, ...$result];
    }
}
