<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Catalog\Models\CatalogItem;

/**
 * ListsCatalogItems port implementation: newest ACTIVE items and
 * SKU resolution under the ambient tenant context.
 */
final class CatalogItemQueries implements ListsCatalogItems
{
    public function activeItems(int $limit): array
    {
        return CatalogItem::query()
            ->where('status', CatalogItem::STATUS_ACTIVE)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(max(1, min(50, $limit)))
            ->get()
            ->map(static fn (CatalogItem $item): array => [
                'id' => (string) $item->getKey(),
                'sku' => $item->sku,
                'name' => $item->name,
                'status' => $item->status,
            ])->all();
    }

    public function itemsBySkus(array $skus): array
    {
        if ($skus === []) {
            return [];
        }

        $items = CatalogItem::query()
            ->where('status', CatalogItem::STATUS_ACTIVE)
            ->whereIn('sku', array_values(array_unique($skus)))
            ->get()
            ->keyBy(static fn (CatalogItem $item): string => $item->sku);

        // Preserve the requested order (popularity rank), drop unknown SKUs.
        $ordered = [];
        foreach ($skus as $sku) {
            if (isset($items[$sku])) {
                $item = $items[$sku];
                $ordered[] = [
                    'id' => (string) $item->getKey(),
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'status' => $item->status,
                ];
            }
        }

        return $ordered;
    }

    /**
     * @param  list<string>  $ids
     * @return list<array{id: string, sku: string, name: string, status: string, price_minor: int}>
     */
    public function itemsByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (string $id): bool => $id !== '')));

        if ($ids === []) {
            return [];
        }

        $items = CatalogItem::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy(static fn (CatalogItem $item): string => (string) $item->getKey());

        // Preserve the requested order, drop unknown ids (fail closed).
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($items[$id])) {
                $item = $items[$id];
                $ordered[] = [
                    'id' => (string) $item->getKey(),
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'status' => $item->status,
                    'price_minor' => (int) $item->price_minor,
                ];
            }
        }

        return $ordered;
    }

    /**
     * @return list<array{id: string, sku: string, name: string, description: string|null, status: string, currency: string, price_minor: int, attributes: array<string, mixed>|null, vendor_id: string|null}>
     */
    public function listForVendor(string $vendorId, int $limit): array
    {
        return CatalogItem::query()
            ->where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(max(1, min(100, $limit)))
            ->get()
            ->map(static fn (CatalogItem $item): array => [
                'id' => (string) $item->getKey(),
                'sku' => $item->sku,
                'name' => $item->name,
                'description' => $item->description,
                'status' => $item->status,
                'currency' => $item->currency,
                'price_minor' => $item->price_minor,
                'attributes' => $item->attributes,
                'vendor_id' => $item->vendor_id,
            ])->all();
    }
}
