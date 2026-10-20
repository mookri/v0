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
 *
 * Full matrix pricing (SKU × pandit × market × currency):
 * - line.pandit_id optional → exact match first, then fallback to base (pandit_id = null)
 * - line.market optional → defaults to 'in'
 * - currency required
 */
final class QuoteCalculator
{
    /**
     * @param list<array{sku: string, quantity: int, pandit_id?: int|null, market?: string|null}> $lines
     * @return array{lines: list<array{sku: string, quantity: int, pandit_id?: int|null, market: string, unit_minor: int, line_minor: int}>, total_minor: int, currency: string}
     */
    public function quote(string $currency, array $lines, CarbonImmutable $at, string $defaultMarket = 'in'): array
    {
        $computed = [];
        $total = 0;

        foreach ($lines as $line) {
            $sku = (string) ($line['sku'] ?? '');
            $quantity = (int) ($line['quantity'] ?? 0);
            $panditId = isset($line['pandit_id']) ? (int) $line['pandit_id'] : null;
            $market = (string) ($line['market'] ?? $defaultMarket);

            if ($sku === '' || $quantity <= 0) {
                throw new QuoteFailedException('invalid-line');
            }

            $item = CatalogItem::query()->where('sku', $sku)->first();
            if ($item === null || $item->status !== CatalogItem::STATUS_ACTIVE) {
                throw new QuoteFailedException("unknown-or-inactive-sku:{$sku}");
            }

            // Try exact pandit match first, then fall back to base price (pandit_id = null)
            $price = null;
            if ($panditId !== null) {
                $price = CatalogPrice::query()
                    ->where('sku', $sku)
                    ->where('currency', $currency)
                    ->where('market', $market)
                    ->where('pandit_id', $panditId)
                    ->where('effective_from', '<=', $at->toDateTimeString())
                    ->where(function ($query) use ($at): void {
                        $query->whereNull('effective_to')->orWhere('effective_to', '>', $at->toDateTimeString());
                    })
                    ->orderByDesc('effective_from')
                    ->first();
            }

            if ($price === null) {
                // Fallback to base price (pandit_id = null)
                $price = CatalogPrice::query()
                    ->where('sku', $sku)
                    ->where('currency', $currency)
                    ->where('market', $market)
                    ->whereNull('pandit_id')
                    ->where('effective_from', '<=', $at->toDateTimeString())
                    ->where(function ($query) use ($at): void {
                        $query->whereNull('effective_to')->orWhere('effective_to', '>', $at->toDateTimeString());
                    })
                    ->orderByDesc('effective_from')
                    ->first();
            }

            if ($price === null) {
                throw new QuoteFailedException("no-price:{$sku}" . ($panditId !== null ? " (pandit_id:{$panditId})" : ''));
            }

            $lineMinor = $price->price_minor * $quantity;
            $total += $lineMinor;
            $computed[] = [
                'sku' => $sku,
                'quantity' => $quantity,
                'pandit_id' => $panditId,
                'market' => $market,
                'unit_minor' => (int) $price->price_minor,
                'line_minor' => $lineMinor,
            ];
        }

        return ['lines' => $computed, 'total_minor' => $total, 'currency' => $currency];
    }
}
