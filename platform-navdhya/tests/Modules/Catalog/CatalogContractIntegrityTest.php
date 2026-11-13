<?php

namespace Tests\Modules\Catalog;

use App\Models\User;
use App\Modules\Catalog\Models\CatalogItem;
use App\Platform\Outbox\OutboxEvent;
use Illuminate\Support\Facades\Route;
use Tests\TenantIntegrationTestCase;

final class CatalogContractIntegrityTest extends TenantIntegrationTestCase
{
    public function test_quote_route_declares_explicit_middleware_inline(): void
    {
        $route = Route::getRoutes()->getByName('catalog.quote');
        $this->assertNotNull($route, 'catalog.quote route should be registered.');

        $middleware = $route->gatherMiddleware();
        $this->assertContains('scope:catalog:read', $middleware, 'Quote route must declare scope:catalog:read explicitly inline.');
        $this->assertContains('throttle.tier', $middleware, 'Quote route must declare throttle.tier explicitly inline.');
    }

    public function test_module_json_permissions_and_events_are_declared(): void
    {
        $manifestPath = base_path('app/Modules/Catalog/module.json');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);

        $permissionCodes = array_column($manifest['permissions'] ?? [], 'code');
        $this->assertContains('catalog:item:view', $permissionCodes);
        $this->assertContains('catalog:item:manage', $permissionCodes);
        $this->assertContains('catalog:taxonomy:view', $permissionCodes);
        $this->assertContains('catalog:taxonomy:create', $permissionCodes);
        $this->assertContains('catalog:taxonomy:update', $permissionCodes);

        $eventNames = array_column($manifest['events'] ?? [], 'name');
        $this->assertContains('catalog.item.created', $eventNames);
        $this->assertContains('catalog.item.updated', $eventNames);
        $this->assertContains('catalog.item.published', $eventNames);
        $this->assertContains('catalog.item.archived', $eventNames);
        $this->assertContains('catalog.price.updated', $eventNames);
        $this->assertContains('catalog.category.moved', $eventNames);
    }

    public function test_catalog_mutations_emit_outbox_facts_cleanly(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:manage']);
        $headers = $this->tenantHeaders($tenant);
        $this->actingAs($user);

        // 1. Create Draft Item
        $resStore = $this->postJson('/api/v1/catalog/items', [
            'sku' => 'RITUAL-SHIVA-ABHISHEK',
            'name' => 'Shiva Rudrabhishek',
            'currency' => 'INR',
            'price_minor' => 250000,
        ], $headers);
        $resStore->assertStatus(201);
        $itemId = $resStore->json('data.id');

        $this->resolveContext($tenant);
        $createdFact = OutboxEvent::query()
            ->where('event_type', 'catalog.item.created')
            ->where('aggregate_id', 'RITUAL-SHIVA-ABHISHEK')
            ->first();
        $this->assertNotNull($createdFact);
        $this->assertSame('catalog_service', $createdFact->aggregate_type);
        $this->clearContext();

        // 2. Publish Item
        $resPublish = $this->postJson("/api/v1/catalog/items/{$itemId}:publish", [], $headers);
        $resPublish->assertStatus(200);

        $this->resolveContext($tenant);
        $publishedFact = OutboxEvent::query()
            ->where('event_type', 'catalog.item.published')
            ->where('aggregate_id', 'RITUAL-SHIVA-ABHISHEK')
            ->first();
        $this->assertNotNull($publishedFact);
        $this->assertSame(CatalogItem::STATUS_ACTIVE, $publishedFact->payload['status']);
        $this->clearContext();

        // 3. Store Price
        $resPrice = $this->postJson("/api/v1/catalog/items/{$itemId}/prices", [
            'currency' => 'INR',
            'price_minor' => 300000,
            'market' => 'in',
        ], $headers);
        $resPrice->assertStatus(201);

        $this->resolveContext($tenant);
        $priceFact = OutboxEvent::query()
            ->where('event_type', 'catalog.price.updated')
            ->where('aggregate_id', 'RITUAL-SHIVA-ABHISHEK')
            ->first();
        $this->assertNotNull($priceFact);
        $this->assertSame(300000, $priceFact->payload['price_minor']);
    }
}
