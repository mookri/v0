<?php

namespace App\Modules\Catalog\Contracts;

/**
 * Catalog read port (deptrac-blessed Contracts surface): other modules
 * (discover feed first) list active items and resolve SKUs without
 * importing catalog models. Ambient tenant context scopes every call.
 *
 * @phpstan-type VendorCatalogItem array{id: string, sku: string, name: string, description: string|null, status: string, currency: string, price_minor: int, attributes: array<string, mixed>|null, vendor_id: string|null}
 */
interface ListsCatalogItems
{
    /**
     * @return list<array{id: string, sku: string, name: string, status: string}>
     */
    public function activeItems(int $limit): array;

    /**
     * @param list<string> $skus
     * @return list<array{id: string, sku: string, name: string, status: string, currency: string}>
     */
    public function itemsBySkus(array $skus): array;

    /**
     * Resolve items by id (temple puja assignments reference pujas by
     * item id). Every status is returned — dashboard displays must name
     * paused items too. Unknown/empty ids are dropped, requested order
     * preserved. price_minor rides along so consumers can price a
     * booking line without a second lookup.
     *
     * @param list<string> $ids
     * @return list<array{id: string, sku: string, name: string, status: string, price_minor: int}>
     */
    public function itemsByIds(array $ids): array;

    /**
     * All items owned by the given vendor (vendor management view: every
     * status, newest first). Empty when the vendor has none.
     *
     * @return list<VendorCatalogItem>
     */
    public function listForVendor(string $vendorId, int $limit): array;
}
