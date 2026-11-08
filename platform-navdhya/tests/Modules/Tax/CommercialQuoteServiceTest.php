<?php

namespace Tests\Modules\Tax;

use App\Modules\Catalog\Contracts\CalculatesQuotes;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Catalog\Models\CatalogPrice;
use App\Modules\Tax\Contracts\CalculatesTax;
use App\Modules\Tax\Domain\CommercialQuoteService;
use App\Modules\Tax\Domain\TaxEngine;
use App\Modules\Tax\Models\CommercialProfile;
use App\Modules\Tax\Models\TaxComputation;
use App\Platform\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

final class CommercialQuoteServiceTest extends TenantIntegrationTestCase
{
    public function test_commercial_quote_calculates_lines_taxes_and_preserves_invariants(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        try {
            // Seed commercial profile with AGENT selling model (10% commission, ₹50 platform fee)
            CommercialProfile::query()->create([
                'tenant_id' => (string) $tenant->getKey(),
                'selling_model' => CommercialProfile::MODEL_AGENT,
                'default_currency' => 'INR',
                'commission_rate_bps' => 1000, // 10.00%
                'platform_fee_minor' => 5000,  // ₹50.00
            ]);

            // Seed catalog items and prices
            $item1 = CatalogItem::query()->create([
                'sku' => 'rudrabhishek-service',
                'name' => 'Rudrabhishek Puja',
                'status' => CatalogItem::STATUS_ACTIVE,
            ]);

            CatalogPrice::query()->create([
                'sku' => 'rudrabhishek-service',
                'currency' => 'INR',
                'market' => 'in',
                'price_minor' => 10000, // ₹100.00
                'effective_from' => now()->subDay(),
            ]);

            $item2 = CatalogItem::query()->create([
                'sku' => 'homa-kit',
                'name' => 'Homa Samagri Kit',
                'status' => CatalogItem::STATUS_ACTIVE,
            ]);

            CatalogPrice::query()->create([
                'sku' => 'homa-kit',
                'currency' => 'INR',
                'market' => 'in',
                'price_minor' => 5000, // ₹50.00
                'effective_from' => now()->subDay(),
            ]);

            /** @var CommercialQuoteService $service */
            $service = app(CommercialQuoteService::class);
            $at = CarbonImmutable::now('UTC');

            $quote = $service->createQuote([
                'tenant_id' => (string) $tenant->getKey(),
                'currency' => 'INR',
                'jurisdiction' => 'IN',
                'supplier_state' => 'MH',
                'customer_state' => 'MH', // Intra-state (18% total: 9% CGST + 9% SGST)
                'lines' => [
                    [
                        'sku' => 'rudrabhishek-service',
                        'quantity' => 2, // 2 x ₹100 = ₹200.00
                        'discount_minor' => 2000, // ₹20.00 discount -> taxable ₹180.00
                    ],
                    [
                        'sku' => 'homa-kit',
                        'quantity' => 1, // 1 x ₹50 = ₹50.00 -> taxable ₹50.00
                    ],
                ],
            ], $at);

            $this->assertTrue($quote['ok']);
            $this->assertSame('IN', $quote['jurisdiction']);
            $this->assertSame(CommercialProfile::MODEL_AGENT, $quote['selling_model']);

            // Verify Line 1:
            // taxable: 18000, tax: 18% of 18000 = 3240 (CGST 1620, SGST 1620), total: 21240
            $l1 = $quote['lines'][0];
            $this->assertSame(18000, $l1['taxable_minor']);
            $this->assertSame(3240, $l1['tax_minor']);
            $this->assertSame(21240, $l1['total_minor']);

            // Verify Line 2:
            // taxable: 5000, tax: 18% of 5000 = 900 (CGST 450, SGST 450), total: 5900
            $l2 = $quote['lines'][1];
            $this->assertSame(5000, $l2['taxable_minor']);
            $this->assertSame(900, $l2['tax_minor']);
            $this->assertSame(5900, $l2['total_minor']);

            // Verify Invariant: sum(lines.total) + platform_fee == orderTotal
            $lineSum = $l1['total_minor'] + $l2['total_minor']; // 21240 + 5900 = 27140
            $this->assertSame(27140, $lineSum);

            $summary = $quote['summary'];
            $this->assertSame(25000, $summary['subtotal_minor']);
            $this->assertSame(2000, $summary['discount_minor']);
            $this->assertSame(4140, $summary['tax_total_minor']); // 3240 + 900
            $this->assertSame(5000, $summary['platform_fee_minor']);
            $this->assertSame(32140, $summary['total_minor']); // 27140 + 5000

            // Commission & Payout:
            // Commissionable: 25000 - 2000 = 23000
            // 10% commission: 2300
            // Payout: 23000 - 2300 = 20700
            $this->assertSame(2300, $summary['commission_minor']);
            $this->assertSame(20700, $summary['payout_minor']);

            // Check quote hash exists and is 64 hex characters
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $quote['quote_hash']);

            // Record Tax Computation
            /** @var TaxEngine $taxEngine */
            $taxEngine = app(TaxEngine::class);
            $computation = $taxEngine->recordComputation(
                (string) $tenant->getKey(),
                $quote['jurisdiction'],
                [
                    'currency' => $quote['currency'],
                    'taxable_amount_minor' => $l1['taxable_minor'] + $l2['taxable_minor'],
                    'tax_amount_minor' => $summary['tax_total_minor'],
                    'total_amount_minor' => $summary['total_minor'],
                    'lines' => $quote['lines'],
                    'summary_components' => $summary['tax_components'],
                ],
                quoteId: 'quote_test_123',
                status: TaxComputation::STATUS_QUOTED
            );

            $this->assertSame(TaxComputation::STATUS_QUOTED, $computation->status);
            $this->assertSame(4140, $computation->tax_amount_minor);
            $this->assertDatabaseHas('tax_computations', [
                'tenant_id' => (string) $tenant->getKey(),
                'quote_id' => 'quote_test_123',
                'tax_amount_minor' => 4140,
            ], 'tenant_shared');
        } finally {
            $this->clearContext();
        }
    }
}
