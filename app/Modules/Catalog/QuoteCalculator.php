<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use Carbon\CarbonImmutable;

/**
 * Deterministic quote calculation (Wave 3 pricing, promotions excluded):
 * each line resolves its effective price at the quote instant; unknown
 * SKUs and currency mismatches fail the whole quote (never partial
 * totals). Integer minor-unit arithmetic only — no floats anywhere.
 */
final class QuoteCalculator
{
    /**
     * @param list<array{sku: string, quantity: int}> $lines
     * @return array{lines: list<array{sku: string, quantity: int, unit_minor: int, line_minor: int}>, total_minor: int, currency: string}
     */
    public function quote(string $currency, array $lines, CarbonImmutable $at): array
    {
        $computed = [];
        $total = 0;

        foreach ($lines as $line) {
            $sku = (string) ($line['sku'] ?? '');
            $quantity = (int) ($line['quantity'] ?? 0);
            if ($sku === '' || $quantity <= 0) {
                throw new QuoteFailedException('invalid-line');
            }

            $item = CatalogItem::query()->where('sku', $sku)->first();
            if ($item === null || $item->status !== CatalogItem::STATUS_ACTIVE) {
                throw new QuoteFailedException("unknown-or-inactive-sku:{$sku}");
            }

            $price = CatalogPrice::query()
                ->where('sku', $sku)
                ->where('currency', $currency)
                ->where('effective_from', '<=', $at->toDateTimeString())
                ->where(function ($query) use ($at): void {
                    $query->whereNull('effective_to')->orWhere('effective_to', '>', $at->toDateTimeString());
                })
                ->orderByDesc('effective_from')
                ->first();

            if ($price === null) {
                throw new QuoteFailedException("no-price:{$sku}");
            }

            $lineMinor = $price->price_minor * $quantity;
            $total += $lineMinor;
            $computed[] = [
                'sku' => $sku,
                'quantity' => $quantity,
                'unit_minor' => (int) $price->price_minor,
                'line_minor' => $lineMinor,
            ];
        }

        return ['lines' => $computed, 'total_minor' => $total, 'currency' => $currency];
    }
}
