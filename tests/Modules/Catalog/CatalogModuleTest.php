<?php

namespace Tests\Modules\Catalog;

use Tests\TenantIntegrationTestCase;

/**
 * Catalog module first vertical: manifest-declared permissions, draft
 * creates, explicit publish verb, per-tenant SKU uniqueness, tenant
 * isolation on reads, capability denial on writes.
 */
final class CatalogModuleTest extends TenantIntegrationTestCase
{
    public function test_crud_publish_and_isolation(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:manage']);
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($user);

        $created = $authed()->postJson('/api/v1/catalog/items', [
            'sku' => 'PUJA-LAKSHMI', 'name' => 'Lakshmi Puja', 'price_minor' => 450000,
        ], $headers)->assertCreated();
        $created->assertJsonPath('data.status', 'DRAFT');
        $id = $created->json('data.id');

        $authed()->postJson('/api/v1/catalog/items', [
            'sku' => 'PUJA-LAKSHMI', 'name' => 'Dupe',
        ], $headers)->assertStatus(409);

        $authed()->getJson("/api/v1/catalog/items/{$id}", $headers)
            ->assertOk()->assertJsonPath('data.sku', 'PUJA-LAKSHMI');

        $authed()->postJson("/api/v1/catalog/items/{$id}:publish", [], $headers)
            ->assertOk()->assertJsonPath('data.status', 'ACTIVE');

        $authed()->postJson("/api/v1/catalog/items/{$id}:publish", [], $headers)
            ->assertStatus(409);

        // Same SKU in another tenant is fine (uniqueness is per tenant).
        $tenantB = $this->makeTenant();
        [$userB] = $this->actingAsMembership($tenantB, ['catalog:item:manage']);
        $this->actingAs($userB)->postJson('/api/v1/catalog/items', [
            'sku' => 'PUJA-LAKSHMI', 'name' => 'Other',
        ], $this->tenantHeaders($tenantB))->assertCreated();

        // Cross-tenant read resolves to nothing.
        $this->actingAs($userB)->getJson("/api/v1/catalog/items/{$id}", $this->tenantHeaders($tenantB))
            ->assertNotFound();
    }

    public function test_writes_require_manage_capability(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        // Reads pass on membership alone.
        $this->actingAs($user)->getJson('/api/v1/catalog/items', $headers)->assertOk();

        // Writes deny without the capability.
        $this->actingAs($user)->postJson('/api/v1/catalog/items', ['sku' => 'X', 'name' => 'X'], $headers)
            ->assertForbidden();
    }

    public function test_update_archive_lifecycle(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:manage']);
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($user);

        $created = $authed()->postJson('/api/v1/catalog/items', ['sku' => 'LIFE', 'name' => 'Life'], $headers)
            ->assertCreated();
        $id = $created->json('data.id');

        $authed()->patchJson("/api/v1/catalog/items/{$id}", ['name' => 'Life v2', 'price_minor' => 999], $headers)
            ->assertOk()
            ->assertJsonPath('data.name', 'Life v2')
            ->assertJsonPath('data.status', 'DRAFT');

        $authed()->postJson("/api/v1/catalog/items/{$id}:archive", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'ARCHIVED');

        // Archived items are immutable; archiving twice conflicts.
        $authed()->patchJson("/api/v1/catalog/items/{$id}", ['name' => 'Nope'], $headers)
            ->assertStatus(409);
        $authed()->postJson("/api/v1/catalog/items/{$id}:archive", [], $headers)
            ->assertStatus(409);
    }
}
