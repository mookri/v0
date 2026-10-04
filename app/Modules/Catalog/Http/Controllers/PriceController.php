<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Modules\Catalog\QuoteCalculator;
use App\Modules\Catalog\QuoteFailedException;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catalog pricing: effective-dated minor-unit prices with supersede on
 * write (exactly one open row per sku/market/currency/pandit), plus a
 * deterministic quote endpoint (unknown SKUs and currency mismatches fail
 * the whole quote — never partial totals).
 *
 * Full matrix: SKU × pandit × market × currency
 * - store: creates price row with optional pandit_id (null = base price for all pandits)
 * - quote: accepts optional pandit_id and market per line; exact match first, then base fallback
 */
final class PriceController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $item = CatalogItem::query()->find($id);

        if ($item === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Item not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $validated = $request->validate([
            'currency' => ['required', 'string', 'size:3'],
            'price_minor' => ['required', 'integer', 'min:0'],
            'market' => ['nullable', 'string', 'max:16'],
            'pandit_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $market = $validated['market'] ?? 'in';
        $panditId = $validated['pandit_id'] ?? null;
        $now = CarbonImmutable::now('UTC');

        // Supersede: close any existing open price for this sku/market/currency/pandit
        $query = CatalogPrice::query()
            ->where('sku', $item->sku)
            ->where('market', $market)
            ->where('currency', $validated['currency'])
            ->whereNull('effective_to');

        if ($panditId !== null) {
            $query->where('pandit_id', $panditId);
        } else {
            $query->whereNull('pandit_id');
        }

        $query->update(['effective_to' => $now->toDateTimeString()]);

        $price = new CatalogPrice();
        $price->forceFill([
            'sku' => $item->sku,
            'market' => $market,
            'currency' => $validated['currency'],
            'pandit_id' => $panditId,
            'price_minor' => $validated['price_minor'],
            'effective_from' => $now->toDateTimeString(),
        ]);
        $price->save();

        return new JsonResponse([
            'data' => [
                'id' => (string) $price->getKey(),
                'object' => 'catalog_price',
                'sku' => $price->sku,
                'market' => $price->market,
                'currency' => $price->currency,
                'pandit_id' => $price->pandit_id,
                'price_minor' => $price->price_minor,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'size:3'],
            'market' => ['nullable', 'string', 'max:16'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.sku' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.pandit_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.market' => ['nullable', 'string', 'max:16'],
        ]);

        $defaultMarket = $validated['market'] ?? 'in';

        try {
            $result = (new QuoteCalculator())->quote(
                $validated['currency'],
                array_map(
                    static fn (array $line): array => [
                        'sku' => $line['sku'],
                        'quantity' => (int) $line['quantity'],
                        'pandit_id' => $line['pandit_id'] ?? null,
                        'market' => $line['market'] ?? null,
                    ],
                    $validated['lines']
                ),
                CarbonImmutable::now('UTC'),
                $defaultMarket
            );
        } catch (QuoteFailedException $e) {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'Quote failed: '.$e->getMessage(), 'request_id' => $this->requestId($request)],
            ], 422);
        }

        return new JsonResponse([
            'data' => array_merge(['object' => 'price_quote'], $result),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
