<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Contracts\ManagesVendorCatalogItems;
use App\Modules\Catalog\Models\CatalogItem;

/**
 * ManagesVendorCatalogItems port implementation: vendor-owned product
 * writes. Wire validation happens at the HTTP edge (Vendor controller);
 * this layer enforces tenancy rules — SKU uniqueness per tenant
 * (TEN-010), DRAFT starts, archived immutability — and scopes every
 * write to the owning vendor.
 */
final class VendorCatalogManagementService implements ManagesVendorCatalogItems
{
    /**
     * @param  array{sku: string, name: string, description?: string|null, currency?: string, price_minor?: int, attributes?: array<string, mixed>|null}  $data
     * @return array{id: string, sku: string, name: string, description: string|null, status: string, currency: string, price_minor: int, attributes: array<string, mixed>|null, vendor_id: string|null}
     */
    public function createForVendor(string $vendorId, array $data): array
    {
        if (CatalogItem::query()->where('sku', $data['sku'])->exists()) {
            throw new \InvalidArgumentException('SKU already exists in this tenant.');
        }

        $item = new CatalogItem();
        $item->forceFill([
            'vendor_id' => $vendorId,
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => CatalogItem::STATUS_DRAFT,
            'currency' => $data['currency'] ?? 'INR',
            'price_minor' => $data['price_minor'] ?? 0,
            'attributes' => $data['attributes'] ?? null,
        ]);
        $item->save();

        return self::shape($item);
    }

    /**
     * @param  array{name?: string, description?: string|null, currency?: string, price_minor?: int, attributes?: array<string, mixed>|null}  $data
     * @return array{id: string, sku: string, name: string, description: string|null, status: string, currency: string, price_minor: int, attributes: array<string, mixed>|null, vendor_id: string|null}
     */
    public function updateForVendor(string $vendorId, string $itemId, array $data): array
    {
        // Missing and another vendor's items are indistinguishable (404).
        $item = CatalogItem::query()
            ->where('vendor_id', $vendorId)
            ->where('id', $itemId)
            ->firstOrFail();

        if ($item->status === CatalogItem::STATUS_ARCHIVED) {
            throw new \InvalidArgumentException('Archived items are immutable.');
        }

        $allowed = array_intersect_key($data, array_flip([
            'name', 'description', 'currency', 'price_minor', 'attributes',
        ]));
        $item->forceFill($allowed)->save();

        return self::shape($item);
    }

    /**
     * @return array{id: string, sku: string, name: string, description: string|null, status: string, currency: string, price_minor: int, attributes: array<string, mixed>|null, vendor_id: string|null}
     */
    private static function shape(CatalogItem $item): array
    {
        return [
            'id' => (string) $item->getKey(),
            'sku' => $item->sku,
            'name' => $item->name,
            'description' => $item->description,
            'status' => $item->status,
            'currency' => $item->currency,
            'price_minor' => $item->price_minor,
            'attributes' => $item->attributes,
            'vendor_id' => $item->vendor_id,
        ];
    }
}
