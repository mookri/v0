<?php

namespace Tests\Modules\Catalog;

use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Catalog\Contracts\ManagesVendorCatalogItems;
use App\Modules\Catalog\Models\CatalogItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Vendor catalog extension (P12a): vendor_id column on catalog_items,
 * vendor-scoped list/create/update through the Catalog ports. Failures
 * cross the module boundary as framework exceptions only (ModelNotFound,
 * InvalidArgumentException) so the Vendor module never imports catalog
 * classes.
 */
final class VendorCatalogItemsTest extends TenantIntegrationTestCase
{
    public function test_vendor_owned_items_round_trip_through_contracts(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $vendorId = (string) Str::ulid();

        $writes = app(ManagesVendorCatalogItems::class);
        $reads = app(ListsCatalogItems::class);

        $created = $writes->createForVendor($vendorId, [
            'sku' => 'VENDOR-PUJA-1',
            'name' => 'Vendor Puja',
            'price_minor' => 19900,
            'attributes' => ['image' => 'puja.png'],
        ]);

        // DRAFT start (publish stays an explicit verb), vendor attached.
        $this->assertSame('DRAFT', $created['status']);
        $this->assertSame($vendorId, $created['vendor_id']);
        $this->assertSame(19900, $created['price_minor']);
        $this->assertSame(['image' => 'puja.png'], $created['attributes']);

        $listed = $reads->listForVendor($vendorId, 50);
        $this->assertCount(1, $listed);
        $this->assertSame($created['id'], $listed[0]['id']);

        // Partial update; SKU is immutable.
        $updated = $writes->updateForVendor($vendorId, $created['id'], [
            'name' => 'Vendor Puja v2',
            'price_minor' => 29900,
        ]);
        $this->assertSame('Vendor Puja v2', $updated['name']);
        $this->assertSame(29900, $updated['price_minor']);
        $this->assertSame('VENDOR-PUJA-1', $updated['sku']);

        // The column is visible on the existing read endpoints (additive).
        $this->clearContext();
        [$user] = $this->actingAsMembership($tenant);
        $this->actingAs($user)->getJson('/api/v1/catalog/items', $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonPath('data.0.vendor_id', $vendorId);
    }

    public function test_sku_uniqueness_spans_vendors_within_a_tenant(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $vendorA = (string) Str::ulid();
        $vendorB = (string) Str::ulid();
        $writes = app(ManagesVendorCatalogItems::class);

        $writes->createForVendor($vendorA, ['sku' => 'SHARED-SKU', 'name' => 'Vendor A']);

        $this->expectException(\InvalidArgumentException::class);
        $writes->createForVendor($vendorB, ['sku' => 'SHARED-SKU', 'name' => 'Vendor B']);
    }

    public function test_same_sku_allowed_in_another_tenant(): void
    {
        $tenantA = $this->makeTenant();
        $this->resolveContext($tenantA);
        app(ManagesVendorCatalogItems::class)->createForVendor(
            (string) Str::ulid(),
            ['sku' => 'TENANT-SKU', 'name' => 'Tenant A'],
        );

        $tenantB = $this->makeTenant();
        $this->resolveContext($tenantB);
        $created = app(ManagesVendorCatalogItems::class)->createForVendor(
            (string) Str::ulid(),
            ['sku' => 'TENANT-SKU', 'name' => 'Tenant B'],
        );

        $this->assertSame('DRAFT', $created['status']);
    }

    public function test_update_scopes_to_the_owning_vendor(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $vendorA = (string) Str::ulid();
        $vendorB = (string) Str::ulid();

        $writes = app(ManagesVendorCatalogItems::class);
        $reads = app(ListsCatalogItems::class);
        $item = $writes->createForVendor($vendorA, ['sku' => 'OWNED', 'name' => 'Owned']);

        // Another vendor in the same tenant sees nothing and cannot touch it.
        $this->assertSame([], $reads->listForVendor($vendorB, 50));

        $this->expectException(ModelNotFoundException::class);
        $writes->updateForVendor($vendorB, $item['id'], ['name' => 'Hijack']);
    }

    public function test_archived_items_are_immutable(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $vendorId = (string) Str::ulid();

        $writes = app(ManagesVendorCatalogItems::class);
        $item = $writes->createForVendor($vendorId, ['sku' => 'ARCH-1', 'name' => 'Archivable']);

        CatalogItem::query()
            ->where('id', $item['id'])
            ->update(['status' => CatalogItem::STATUS_ARCHIVED]);

        $this->expectException(\InvalidArgumentException::class);
        $writes->updateForVendor($vendorId, $item['id'], ['name' => 'Nope']);
    }

    public function test_vendor_items_isolate_across_tenants(): void
    {
        $tenantA = $this->makeTenant();
        $this->resolveContext($tenantA);
        $vendorId = (string) Str::ulid();
        $item = app(ManagesVendorCatalogItems::class)->createForVendor(
            $vendorId,
            ['sku' => 'TENANT-A-ITEM', 'name' => 'Tenant A item'],
        );

        $tenantB = $this->makeTenant();
        $this->resolveContext($tenantB);

        // Same vendor id, other tenant: invisible and untouchable.
        $this->assertSame([], app(ListsCatalogItems::class)->listForVendor($vendorId, 50));

        $this->expectException(ModelNotFoundException::class);
        app(ManagesVendorCatalogItems::class)->updateForVendor($vendorId, $item['id'], ['name' => 'Hijack']);
    }
}
