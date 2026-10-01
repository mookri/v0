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
 * write (exactly one open row per sku/market/currency), plus a
 * deterministic quote endpoint (unknown SKUs and currency mismatches fail
 * the whole quote — never partial totals).
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
        ]);

        $market = $validated['market'] ?? 'in';
        $now = CarbonImmutable::now('UTC');

        CatalogPrice::query()
            ->where('sku', $item->sku)
            ->where('market', $market)
            ->where('currency', $validated['currency'])
            ->whereNull('effective_to')
            ->update(['effective_to' => $now->toDateTimeString()]);

        $price = new CatalogPrice();
        $price->forceFill([
            'sku' => $item->sku,
            'market' => $market,
            'currency' => $validated['currency'],
            'price_minor' => $validated['price_minor'],
            'effective_from' => $now->toDateTimeString(),
        ]);
        $price->save();

        return new JsonResponse([
            'data' => [
                'id' => (string) $price->getKey(),
                'object' => 'catalog_price',
                'sku' => $price->sku,
                'currency' => $price->currency,
                'price_minor' => $price->price_minor,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'size:3'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.sku' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $result = (new QuoteCalculator())->quote(
                $validated['currency'],
                array_map(
                    static fn (array $line): array => ['sku' => $line['sku'], 'quantity' => (int) $line['quantity']],
                    $validated['lines']
                ),
                CarbonImmutable::now('UTC')
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
