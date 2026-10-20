<?php

namespace Tests\Modules\Catalog;

use App\Modules\Catalog\Models\CatalogItem;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Public catalog browse (B1): the marketing site's Quick Book lists
 * ACTIVE items for one ACTIVE tenant with no session at all — proving
 * the route opts out of auth.any + tenant cleanly, exposes a minimal
 * field set, and never leaks another tenant's rows or draft/archived
 * items.
 */
final class PublicCatalogTest extends TenantIntegrationTestCase
{
    public function test_anonymous_read_returns_active_items_of_that_tenant_only(): void
    {
        $tenant = $this->makeTenant();
        $otherTenant = $this->makeTenant();

        $active = CatalogItem::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'status' => CatalogItem::STATUS_ACTIVE,
            'name' => 'Active Puja',
            'price_minor' => 49900,
        ]);
        CatalogItem::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'status' => CatalogItem::STATUS_DRAFT,
        ]);
        CatalogItem::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'status' => CatalogItem::STATUS_ARCHIVED,
        ]);
        CatalogItem::factory()->create([
            'tenant_id' => (string) $otherTenant->getKey(),
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        // No actingAs, no tenant header — anonymous by construction.
        $response = $this->getJson('/api/v1/catalog/public/items?tenant_id='.(string) $tenant->getKey());

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=60', $cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', (string) $active->getKey());
        $response->assertJsonPath('data.0.sku', $active->sku);
        $response->assertJsonPath('data.0.price_minor', 49900);
        $response->assertJsonPath('data.0.currency', 'INR');

        // Minimal field set: no attributes, no vendor internals, no
        // status lifecycle exposure on a public surface.
        $this->assertSame(
            ['id', 'object', 'sku', 'name', 'description', 'currency', 'price_minor'],
            array_keys((array) $response->json('data.0')),
        );
    }

    public function test_unknown_or_suspended_tenant_is_not_found(): void
    {
        $suspended = $this->makeTenant(['state' => Tenant::STATE_SUSPENDED]);

        $this->getJson('/api/v1/catalog/public/items?tenant_id='.(string) $suspended->getKey())
            ->assertNotFound();

        $this->getJson('/api/v1/catalog/public/items?tenant_id='.str_pad('01J', 26, 'F'))
            ->assertNotFound();
    }

    public function test_missing_tenant_id_is_a_validation_error(): void
    {
        $this->getJson('/api/v1/catalog/public/items')
            ->assertStatus(422);
    }

    public function test_item_detail_is_never_served_on_the_public_surface(): void
    {
        $tenant = $this->makeTenant();
        $item = CatalogItem::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        // The public route is list-only: an item id does not resolve.
        $this->getJson('/api/v1/catalog/public/items/'.$item->getKey())
            ->assertNotFound();
    }
}
