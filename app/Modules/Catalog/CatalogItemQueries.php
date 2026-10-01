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
}
