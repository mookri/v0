<?php

namespace Tests\Modules\Catalog;

use App\Modules\Catalog\Models\CatalogCategory;
use App\Modules\Catalog\Models\CatalogItem;
use Database\Seeders\CatalogTaxonomySeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Tests\TenantIntegrationTestCase;

final class CategoryTreeIntegrityTest extends TenantIntegrationTestCase
{
    public function test_categories_tree_structure_and_materialized_path(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        // 1. Create Root Category
        $root = CatalogCategory::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Vedic Havans',
            'slug' => 'vedic-havans',
            'kind' => CatalogCategory::KIND_HAVANS,
        ]);

        $this->assertSame(0, $root->depth);
        $this->assertSame("/{$root->id}/", $root->path);

        // 2. Create Child Category
        $child = CatalogCategory::query()->create([
            'tenant_id' => $tenantId,
            'parent_id' => $root->id,
            'name' => 'Maha Ganapati Homa',
            'slug' => 'maha-ganapati-homa',
        ]);

        $this->assertSame(1, $child->depth);
        $this->assertSame("/{$root->id}/{$child->id}/", $child->path);
        $this->assertSame(CatalogCategory::KIND_HAVANS, $child->kind); // Inherited
    }

    public function test_null_uniqueness_trap_is_mitigated_across_dialects(): void
    {
        // 1. Create Platform Global Category (tenant_id = null)
        CatalogCategory::query()->withoutGlobalScopes()->create([
            'tenant_id' => null,
            'name' => 'Global Rituals',
            'slug' => 'global-rituals',
            'kind' => CatalogCategory::KIND_RITUALS,
        ]);

        // 2. Second Platform Global Category with identical slug must fail unique constraint
        $this->expectException(UniqueConstraintViolationException::class);
        CatalogCategory::query()->withoutGlobalScopes()->create([
            'tenant_id' => null,
            'name' => 'Duplicate Global Rituals',
            'slug' => 'global-rituals',
            'kind' => CatalogCategory::KIND_RITUALS,
        ]);
    }

    public function test_tenant_specific_slug_isolates_and_permits_cross_tenant_reuse(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        // Tenant A creates category
        $catA = CatalogCategory::query()->withoutGlobalScopes()->create([
            'tenant_id' => (string) $tenantA->getKey(),
            'name' => 'Special Pujas',
            'slug' => 'special-pujas',
            'kind' => CatalogCategory::KIND_RITUALS,
        ]);
        $this->assertNotNull($catA);

        // Tenant B can create same slug without collision
        $catB = CatalogCategory::query()->withoutGlobalScopes()->create([
            'tenant_id' => (string) $tenantB->getKey(),
            'name' => 'Special Pujas Tenant B',
            'slug' => 'special-pujas',
            'kind' => CatalogCategory::KIND_RITUALS,
        ]);
        $this->assertNotNull($catB);

        // Duplicate slug within Tenant A must fail
        $this->expectException(UniqueConstraintViolationException::class);
        CatalogCategory::query()->withoutGlobalScopes()->create([
            'tenant_id' => (string) $tenantA->getKey(),
            'name' => 'Another Special Pujas in A',
            'slug' => 'special-pujas',
            'kind' => CatalogCategory::KIND_RITUALS,
        ]);
    }

    public function test_public_categories_and_items_subtree_filtering(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        // Run Taxonomy Seeder
        (new CatalogTaxonomySeeder())->run();

        $havansRoot = CatalogCategory::query()->withoutGlobalScopes()->where('slug', 'havans')->firstOrFail();
        $ganapatiChild = CatalogCategory::query()->withoutGlobalScopes()->where('slug', 'ganapati-homa')->firstOrFail();

        // Create Item under Ganapati Homa
        $item1 = CatalogItem::query()->create([
            'tenant_id' => $tenantId,
            'category_id' => $ganapatiChild->id,
            'sku' => 'PUJA-GANAPATI-01',
            'name' => 'Ganapati Homa Deluxe',
            'status' => CatalogItem::STATUS_ACTIVE,
            'price_minor' => 500000,
        ]);

        // Create Item under Rituals
        $ritualsRoot = CatalogCategory::query()->withoutGlobalScopes()->where('slug', 'rituals')->firstOrFail();
        $item2 = CatalogItem::query()->create([
            'tenant_id' => $tenantId,
            'category_id' => $ritualsRoot->id,
            'sku' => 'PUJA-SATYANARAYAN-01',
            'name' => 'Satyanarayan Vrat',
            'status' => CatalogItem::STATUS_ACTIVE,
            'price_minor' => 400000,
        ]);

        $this->clearContext();

        // 1. Public Categories API returns nested tree
        $resCats = $this->getJson("/api/v1/catalog/public/categories?tenant_id={$tenantId}");
        $resCats->assertStatus(200);
        $resCats->assertJsonPath('data.0.kind', 'RITUALS');
        $this->assertNotEmpty($resCats->json('data.0.children'));

        // 2. Querying items under 'havans' returns Ganapati Homa because it is in the subtree
        $resHavans = $this->getJson("/api/v1/catalog/public/items?tenant_id={$tenantId}&category=havans");
        $resHavans->assertStatus(200);
        $havansItems = $resHavans->json('data');
        $this->assertCount(1, $havansItems);
        $this->assertSame('PUJA-GANAPATI-01', $havansItems[0]['sku']);

        // 3. Querying items under 'rituals' returns Satyanarayan
        $resRituals = $this->getJson("/api/v1/catalog/public/items?tenant_id={$tenantId}&category=rituals");
        $resRituals->assertStatus(200);
        $ritualsItems = $resRituals->json('data');
        $this->assertCount(1, $ritualsItems);
        $this->assertSame('PUJA-SATYANARAYAN-01', $ritualsItems[0]['sku']);
    }
}

