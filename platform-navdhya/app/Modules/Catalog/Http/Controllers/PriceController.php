<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Modules\Catalog\Outbox\CatalogFactAppender;
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
    private readonly CatalogFactAppender $facts;

    public function __construct(?CatalogFactAppender $facts = null)
    {
        $this->facts = $facts ?? app(CatalogFactAppender::class);
    }

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
            // Pandit ids are ULIDs (26 characters), not integers: an integer
            // column could never hold one, so a pandit-specific price was
            // unreachable and the quote always fell back to the base price.
            'pandit_id' => ['nullable', 'string', 'ulid'],
            'package_tier_code' => ['nullable', 'string', 'max:32'],
            'qualification_tier_code' => ['nullable', 'string', 'max:32'],
        ]);

        $market = $validated['market'] ?? 'in';
        $panditId = $validated['pandit_id'] ?? null;
        $packageTier = $validated['package_tier_code'] ?? null;
        $qualificationTier = $validated['qualification_tier_code'] ?? null;
        $now = CarbonImmutable::now('UTC');

        // Supersede within locked transaction for single-active price concurrency
        $price = \Illuminate\Support\Facades\DB::connection('tenant_shared')->transaction(function () use (
            $item, $market, $validated, $panditId, $packageTier, $qualificationTier, $now
        ): CatalogPrice {
            $query = CatalogPrice::query()
                ->where('sku', $item->sku)
                ->where('market', $market)
                ->where('currency', $validated['currency'])
                ->whereNull('effective_to')
                ->lockForUpdate();

            if ($panditId !== null) {
                $query->where('pandit_id', $panditId);
            } else {
                $query->whereNull('pandit_id');
            }

            if ($packageTier !== null) {
                $query->where('package_tier_code', $packageTier);
            } else {
                $query->whereNull('package_tier_code');
            }

            if ($qualificationTier !== null) {
                $query->where('qualification_tier_code', $qualificationTier);
            } else {
                $query->whereNull('qualification_tier_code');
            }

            $existing = $query->get();
            $maxVersion = 0;
            foreach ($existing as $row) {
                $maxVersion = max($maxVersion, (int) $row->version);
                $row->update([
                    'effective_to' => $now->toDateTimeString(),
                    'is_current' => false,
                ]);
            }

            $price = new CatalogPrice();
            $price->forceFill([
                'sku' => $item->sku,
                'market' => $market,
                'currency' => $validated['currency'],
                'pandit_id' => $panditId,
                'package_tier_code' => $packageTier,
                'qualification_tier_code' => $qualificationTier,
                'price_minor' => $validated['price_minor'],
                'version' => $maxVersion + 1,
                'is_current' => true,
                'effective_from' => $now->toDateTimeString(),
            ]);
            $price->save();

            return $price;
        });

        $this->facts->append('catalog.price.updated', $item->sku, [
            'sku' => $item->sku,
            'market' => $price->market,
            'currency' => $price->currency,
            'price_minor' => $price->price_minor,
            'pandit_id' => $price->pandit_id,
            'package_tier_code' => $price->package_tier_code,
            'qualification_tier_code' => $price->qualification_tier_code,
            'version' => $price->version,
        ]);

        return new JsonResponse([
            'data' => [
                'id' => (string) $price->getKey(),
                'object' => 'catalog_price',
                'sku' => $price->sku,
                'market' => $price->market,
                'currency' => $price->currency,
                'pandit_id' => $price->pandit_id,
                'package_tier_code' => $price->package_tier_code,
                'qualification_tier_code' => $price->qualification_tier_code,
                'price_minor' => $price->price_minor,
                'version' => $price->version,
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
            'lines.*.pandit_id' => ['nullable', 'string', 'ulid'],
            'lines.*.package_tier' => ['nullable', 'string', 'max:32'],
            'lines.*.qualification_tier' => ['nullable', 'string', 'max:32'],
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
                        'package_tier' => $line['package_tier'] ?? null,
                        'qualification_tier' => $line['qualification_tier'] ?? null,
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
