<?php

namespace App\Modules\Catalog\Contracts;

/**
 * Vendor-scoped catalog write port (deptrac-blessed Contracts surface):
 * the Vendor module creates and updates its products through this port
 * without importing catalog models. Ambient tenant context scopes every
 * call; SKU uniqueness stays per tenant (TEN-010). Failures are framework
 * exceptions only (ModelNotFoundException / \InvalidArgumentException) so
 * callers never import catalog classes across the module boundary.
 *
 * @phpstan-type VendorCatalogItem array{id: string, sku: string, name: string, description: string|null, status: string, currency: string, price_minor: int, attributes: array<string, mixed>|null, vendor_id: string|null}
 */
interface ManagesVendorCatalogItems
{
    /**
     * Create a vendor-owned item; starts DRAFT (publish stays an explicit
     * catalog verb). Input is validated at the HTTP edge.
     *
     * @param  array{
     *   sku: string,
     *   name: string,
     *   description?: string|null,
     *   currency?: string,
     *   price_minor?: int,
     *   attributes?: array<string, mixed>|null
     * }  $data
     * @return VendorCatalogItem
     *
     * @throws \InvalidArgumentException when the SKU already exists in the tenant
     */
    public function createForVendor(string $vendorId, array $data): array;

    /**
     * Update an item owned by the vendor (partial update; SKU is immutable,
     * archived items are immutable).
     *
     * @param  array{
     *   name?: string,
     *   description?: string|null,
     *   currency?: string,
     *   price_minor?: int,
     *   attributes?: array<string, mixed>|null
     * }  $data
     * @return VendorCatalogItem
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when missing or owned by another vendor
     * @throws \InvalidArgumentException when the item is archived
     */
    public function updateForVendor(string $vendorId, string $itemId, array $data): array;
}
