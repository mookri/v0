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
     * @return list<array{id: string, sku: string, name: string, status: string}>
     */
    public function itemsBySkus(array $skus): array;

    /**
     * All items owned by the given vendor (vendor management view: every
     * status, newest first). Empty when the vendor has none.
     *
     * @return list<VendorCatalogItem>
     */
    public function listForVendor(string $vendorId, int $limit): array;
}
