<?php

namespace Tests\Modules\Catalog;

use App\Modules\Catalog\Models\CatalogPrice;
use Tests\TenantIntegrationTestCase;

/**
 * Pandit-specific pricing (BACKEND-RCA 2026-10-21, finding W6).
 *
 * `catalog_prices.pandit_id` was an unsigned bigint while every pandit
 * id is a 26 character ULID, so a pandit-specific price could never be
 * stored or matched: the quote silently fell back to the base price
 * and a `pandit_id` of 1 addressed a pandit that does not exist.
 */
final class CatalogPanditPricingTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: string, 1: \App\Models\User, 2: string}
     */
    private function pricedItem(string $sku = 'PANDIT-PRICE-1'): array
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['catalog:item:manage']);
        $headers = $this->tenantHeaders($tenant);

        $created = $this->actingAs($user)->postJson('/api/v1/catalog/items', [
            'sku' => $sku, 'name' => 'Priced',
        ], $headers)->assertCreated();

        $id = (string) $created->json('data.id');
        $this->actingAs($user)->postJson("/api/v1/catalog/items/{$id}:publish", [], $headers)->assertOk();

        return [(string) $tenant->getKey(), $user, $id];
    }

    public function test_a_pandit_specific_price_wins_over_the_base_price(): void
    {
        [$tenantId, $user, $id] = $this->pricedItem();
        $headers = $this->tenantHeaders(\App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId));
        $panditId = (string) \Illuminate\Support\Str::ulid();

        // Base price for everyone.
        $this->actingAs($user)->postJson("/api/v1/catalog/items/{$id}/prices", [
            'currency' => 'INR', 'price_minor' => 450000,
        ], $headers)->assertCreated();

        // Pandit-specific price (a ULID, not an integer).
        $this->actingAs($user)->postJson("/api/v1/catalog/items/{$id}/prices", [
            'currency' => 'INR', 'price_minor' => 300000, 'pandit_id' => $panditId,
        ], $headers)->assertCreated();

        // The ULID really is stored as a ULID.
        $row = CatalogPrice::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('pandit_id')
            ->first();

        $this->assertNotNull($row, 'A pandit-specific price must be storable.');
        $this->assertSame($panditId, (string) $row->pandit_id);

        // The quote for THAT pandit uses the pandit price.
        $quote = $this->actingAs($user)->postJson('/api/v1/catalog/quote', [
            'currency' => 'INR',
            'lines' => [['sku' => 'PANDIT-PRICE-1', 'quantity' => 1, 'pandit_id' => $panditId]],
        ], $headers)->assertOk();

        $quote->assertJsonPath('data.total_minor', 300000);
        $quote->assertJsonPath('data.lines.0.unit_minor', 300000);

        // A different pandit still gets the base price.
        $other = $this->actingAs($user)->postJson('/api/v1/catalog/quote', [
            'currency' => 'INR',
            'lines' => [['sku' => 'PANDIT-PRICE-1', 'quantity' => 1, 'pandit_id' => (string) \Illuminate\Support\Str::ulid()]],
        ], $headers)->assertOk();

        $other->assertJsonPath('data.total_minor', 450000);
    }

    public function test_a_pandit_id_must_be_a_ulid(): void
    {
        [$tenantId, $user, $id] = $this->pricedItem();
        $headers = $this->tenantHeaders(\App\Platform\Tenancy\Models\Tenant::query()->findOrFail($tenantId));

        $this->actingAs($user)->postJson("/api/v1/catalog/items/{$id}/prices", [
            'currency' => 'INR', 'price_minor' => 300000, 'pandit_id' => 'not-a-ulid',
        ], $headers)->assertStatus(422);
    }
}
