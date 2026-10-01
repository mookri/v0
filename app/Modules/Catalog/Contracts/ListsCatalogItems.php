<?php

namespace App\Modules\Catalog\Contracts;

/**
 * Catalog read port (deptrac-blessed Contracts surface): other modules
 * (discover feed first) list active items and resolve SKUs without
 * importing catalog models. Ambient tenant context scopes every call.
 */
interface ListsCatalogItems
{
    /**
     * @return list<array{id: string, sku: string, name: string, status: string}>
     */
    public function activeItems(int $limit): array;

    /**
     * @param list<string> $skus
     * @return list<array{id: string, sku: string, name: string, status: string}>
     */
    public function itemsBySkus(array $skus): array;
}
