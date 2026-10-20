<?php

namespace Tests\Modules\Catalog;

use Tests\TenantIntegrationTestCase;

/**
 * Catalog pricing: effective-dated minor-unit prices with supersede on
 * write, deterministic quotes, whole-quote failure on unknown SKUs or
 * currency mismatches (never partial totals).
 */
final class CatalogPricingTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: string, 1: string}
     */
    private function activeItem(string $sku = 'PRICE-1'): array
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:manage']);
        $headers = $this->tenantHeaders($tenant);

        $created = $this->actingAs($user)->postJson('/api/v1/catalog/items', [
            'sku' => $sku, 'name' => 'Priced',
        ], $headers)->assertCreated();
        $id = $created->json('data.id');
        $this->actingAs($user)->postJson("/api/v1/catalog/items/{$id}:publish", [], $headers)->assertOk();

        return [$tenant, $user, $id];
    }

    public function test_price_supersede_and_deterministic_quote(): void
    {
        [$tenant, $user, $id] = $this->activeItem();
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($user);

        $authed()->postJson("/api/v1/catalog/items/{$id}/prices", [
            'currency' => 'INR', 'price_minor' => 450000,
        ], $headers)->assertCreated();
        $authed()->postJson("/api/v1/catalog/items/{$id}/prices", [
            'currency' => 'INR', 'price_minor' => 400000,
        ], $headers)->assertCreated();

        $quote = $authed()->postJson('/api/v1/catalog/quote', [
            'currency' => 'INR',
            'lines' => [['sku' => 'PRICE-1', 'quantity' => 2]],
        ], $headers)->assertOk();
        // Latest effective price wins: 400000 × 2.
        $quote->assertJsonPath('data.total_minor', 800000);
        $quote->assertJsonPath('data.lines.0.unit_minor', 400000);

        // Unknown SKU fails the whole quote.
        $authed()->postJson('/api/v1/catalog/quote', [
            'currency' => 'INR',
            'lines' => [['sku' => 'PRICE-1', 'quantity' => 1], ['sku' => 'GHOST', 'quantity' => 1]],
        ], $headers)->assertStatus(422);

        // Currency mismatch fails the whole quote.
        $authed()->postJson('/api/v1/catalog/quote', [
            'currency' => 'USD',
            'lines' => [['sku' => 'PRICE-1', 'quantity' => 1]],
        ], $headers)->assertStatus(422);
    }
}
