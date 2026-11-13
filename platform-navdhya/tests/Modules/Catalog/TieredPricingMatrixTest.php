<?php

namespace Tests\Modules\Catalog;

use App\Modules\Catalog\Domain\Pricing\TieredPriceResolver;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPackageTier;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Platform\Identity\Models\Tenant;
use App\Platform\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

final class TieredPricingMatrixTest extends TenantIntegrationTestCase
{
    private string $tenantId;
    private $tenant;
    private $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = $this->makeTenant();
        $this->tenantId = (string) $this->tenant->getKey();

        [$this->adminUser] = $this->actingAsMembership($this->tenant, ['catalog:item:manage']);
    }

    public function test_five_step_pricing_precedence_ladder(): void
    {
        $sku = 'SRV-PRECEDENCE-TEST';
        $item = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'name' => 'Precedence Test Ritual',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 60,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $now = CarbonImmutable::now('UTC');
        $panditId = (string) Str::ulid();

        // 1. Base price: 5,000 INR
        CatalogPrice::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'market' => 'in',
            'currency' => 'INR',
            'price_minor' => 500000,
            'effective_from' => $now->subHour(),
            'is_current' => true,
        ]);

        // 2. Qualification tier price (VASHISHTHA): 7,000 INR
        CatalogPrice::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'market' => 'in',
            'currency' => 'INR',
            'qualification_tier_code' => 'VASHISHTHA',
            'price_minor' => 700000,
            'effective_from' => $now->subHour(),
            'is_current' => true,
        ]);

        // 3. Package tier price (PREMIUM): 8,000 INR
        CatalogPrice::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'market' => 'in',
            'currency' => 'INR',
            'package_tier_code' => 'PREMIUM',
            'price_minor' => 800000,
            'effective_from' => $now->subHour(),
            'is_current' => true,
        ]);

        // 4. Two-axis combination (PREMIUM x VASHISHTHA): 10,000 INR
        CatalogPrice::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'market' => 'in',
            'currency' => 'INR',
            'package_tier_code' => 'PREMIUM',
            'qualification_tier_code' => 'VASHISHTHA',
            'price_minor' => 1000000,
            'effective_from' => $now->subHour(),
            'is_current' => true,
        ]);

        // 5. Direct pandit override: 12,000 INR
        CatalogPrice::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'market' => 'in',
            'currency' => 'INR',
            'pandit_id' => $panditId,
            'price_minor' => 1200000,
            'effective_from' => $now->subHour(),
            'is_current' => true,
        ]);

        $resolver = new TieredPriceResolver();

        // Step 5 test: Base SKU price alone
        $r1 = $resolver->resolve(sku: $sku, currency: 'INR', market: 'in', at: $now, tenantId: $this->tenantId);
        $this->assertNotNull($r1);
        $this->assertSame(500000, $r1->priceMinor);
        $this->assertSame(TieredPriceResolver::RULE_BASE_SKU, $r1->rule);

        // Step 4 test: Qualification tier alone (VASHISHTHA)
        $r2 = $resolver->resolve(sku: $sku, currency: 'INR', market: 'in', at: $now, qualificationTier: 'VASHISHTHA', tenantId: $this->tenantId);
        $this->assertNotNull($r2);
        $this->assertSame(700000, $r2->priceMinor);
        $this->assertSame(TieredPriceResolver::RULE_QUALIFICATION_TIER, $r2->rule);

        // Step 3 test: Package tier alone (PREMIUM)
        $r3 = $resolver->resolve(sku: $sku, currency: 'INR', market: 'in', at: $now, packageTier: 'PREMIUM', tenantId: $this->tenantId);
        $this->assertNotNull($r3);
        $this->assertSame(800000, $r3->priceMinor);
        $this->assertSame(TieredPriceResolver::RULE_PACKAGE_TIER, $r3->rule);

        // Step 2 test: Combined Two-Axis match (PREMIUM x VASHISHTHA)
        $r4 = $resolver->resolve(sku: $sku, currency: 'INR', market: 'in', at: $now, packageTier: 'PREMIUM', qualificationTier: 'VASHISHTHA', tenantId: $this->tenantId);
        $this->assertNotNull($r4);
        $this->assertSame(1000000, $r4->priceMinor);
        $this->assertSame(TieredPriceResolver::RULE_PACKAGE_AND_QUALIFICATION, $r4->rule);

        // Step 1 test: Direct Pandit override beats all package/qualification tiers
        $r5 = $resolver->resolve(
            sku: $sku,
            currency: 'INR',
            market: 'in',
            at: $now,
            panditId: $panditId,
            packageTier: 'PREMIUM',
            qualificationTier: 'VASHISHTHA',
            tenantId: $this->tenantId
        );
        $this->assertNotNull($r5);
        $this->assertSame(1200000, $r5->priceMinor);
        $this->assertSame(TieredPriceResolver::RULE_PANDIT_OVERRIDE, $r5->rule);
    }

    public function test_price_store_supersedes_cleanly_with_version_increment_and_lock(): void
    {
        $item = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-TIER-SUPERSEDE',
            'name' => 'Tier Supersede Service',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 60,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        // 1. Post initial price for STANDARD package tier
        $res1 = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/catalog/items/{$item->id}/prices", [
                'currency' => 'INR',
                'price_minor' => 250000,
                'package_tier_code' => 'STANDARD',
            ], $this->tenantHeaders($this->tenant));

        $res1->assertStatus(201);
        $res1->assertJsonPath('data.version', 1);
        $res1->assertJsonPath('data.price_minor', 250000);

        // 2. Post updated price for the SAME STANDARD package tier (supersede)
        $res2 = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/catalog/items/{$item->id}/prices", [
                'currency' => 'INR',
                'price_minor' => 300000,
                'package_tier_code' => 'STANDARD',
            ], $this->tenantHeaders($this->tenant));

        $res2->assertStatus(201);
        $res2->assertJsonPath('data.version', 2);
        $res2->assertJsonPath('data.price_minor', 300000);

        // Verify previous row is now closed and is_current is false
        $prices = CatalogPrice::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->where('sku', 'SRV-TIER-SUPERSEDE')
            ->where('package_tier_code', 'STANDARD')
            ->orderBy('version')
            ->get();

        $this->assertCount(2, $prices);
        $this->assertFalse($prices[0]->is_current);
        $this->assertNotNull($prices[0]->effective_to);
        $this->assertSame(1, $prices[0]->version);

        $this->assertTrue($prices[1]->is_current);
        $this->assertNull($prices[1]->effective_to);
        $this->assertSame(2, $prices[1]->version);
    }

    public function test_catalog_assert_single_active_price_command(): void
    {
        $item = CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-ASSERT-PRICE',
            'name' => 'Assert Price Item',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 60,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        CatalogPrice::create([
            'tenant_id' => $this->tenantId,
            'sku' => 'SRV-ASSERT-PRICE',
            'market' => 'in',
            'currency' => 'INR',
            'price_minor' => 100000,
            'is_current' => true,
            'effective_from' => now()->subHour(),
        ]);

        $this->artisan('catalog:assert-single-active-price', ['--tenant' => $this->tenantId])
            ->assertExitCode(0);
    }

    public function test_quote_endpoint_returns_line_rule_and_tier_ladder(): void
    {
        $sku = 'SRV-QUOTE-LADDER';
        CatalogItem::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'name' => 'Quote Ladder Service',
            'item_kind' => CatalogItem::KIND_SERVICE,
            'base_duration_minutes' => 60,
            'status' => CatalogItem::STATUS_ACTIVE,
        ]);

        $now = CarbonImmutable::now('UTC');

        // Base price
        CatalogPrice::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'market' => 'in',
            'currency' => 'INR',
            'price_minor' => 400000,
            'effective_from' => $now->subHour(),
            'is_current' => true,
        ]);

        // STANDARD tier price
        CatalogPrice::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'market' => 'in',
            'currency' => 'INR',
            'package_tier_code' => 'STANDARD',
            'price_minor' => 450000,
            'effective_from' => $now->subHour(),
            'is_current' => true,
        ]);

        // PREMIUM tier price
        CatalogPrice::create([
            'tenant_id' => $this->tenantId,
            'sku' => $sku,
            'market' => 'in',
            'currency' => 'INR',
            'package_tier_code' => 'PREMIUM',
            'price_minor' => 650000,
            'effective_from' => $now->subHour(),
            'is_current' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson('/api/v1/catalog/quote', [
            'currency' => 'INR',
            'lines' => [
                [
                    'sku' => $sku,
                    'quantity' => 1,
                    'package_tier' => 'PREMIUM',
                ],
            ],
        ], $this->tenantHeaders($this->tenant));

        $response->assertOk();
        $response->assertJsonPath('data.total_minor', 650000);
        $response->assertJsonPath('data.lines.0.effective_rule', 'package_tier');
        $response->assertJsonPath('data.lines.0.unit_minor', 650000);

        // Tier ladder presence
        $this->assertNotEmpty($response->json('data.tier_ladder'));
        $response->assertJsonPath('data.tier_ladder.0.tier_code', 'STANDARD');
        $response->assertJsonPath('data.tier_ladder.0.price_minor', 450000);
        $response->assertJsonPath('data.tier_ladder.1.tier_code', 'PREMIUM');
        $response->assertJsonPath('data.tier_ladder.1.price_minor', 650000);
    }
}
