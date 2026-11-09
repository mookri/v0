<?php

namespace Tests\Modules\Tax;

use App\Modules\Tax\Contracts\CalculatesTax;
use App\Modules\Tax\Domain\TaxEngine;
use PHPUnit\Framework\TestCase;

final class TaxEngineTest extends TestCase
{
    private CalculatesTax $taxEngine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxEngine = new TaxEngine(
            new \App\Modules\Tax\Domain\Jurisdictions\IndiaGstCalculator(),
            new \App\Modules\Tax\Domain\Jurisdictions\UnitedStatesSalesTaxCalculator(),
            new \App\Modules\Tax\Domain\Jurisdictions\UnitedKingdomVatCalculator(),
            new \App\Modules\Tax\Domain\Jurisdictions\UnitedArabEmiratesVatCalculator(),
        );
    }

    public function test_india_gst_intra_state_splits_cgst_and_sgst(): void
    {
        $result = $this->taxEngine->calculate([
            'tenant_id' => 'tenant_test_1',
            'jurisdiction' => 'IN',
            'currency' => 'INR',
            'supplier_state' => 'MH',
            'customer_state' => 'MH',
            'lines' => [
                [
                    'sku' => 'satyanarayan-puja',
                    'quantity' => 1,
                    'unit_price_minor' => 10000, // ₹100.00
                ],
            ],
        ]);

        $this->assertSame(10000, $result['taxable_amount_minor']);
        $this->assertSame(1800, $result['tax_amount_minor']); // 18% = ₹18.00
        $this->assertSame(11800, $result['total_amount_minor']);

        $line = $result['lines'][0];
        $this->assertCount(2, $line['components']);
        $this->assertSame('CGST', $line['components'][0]['name']);
        $this->assertSame(900, $line['components'][0]['amount_minor']); // 9%
        $this->assertSame('SGST', $line['components'][1]['name']);
        $this->assertSame(900, $line['components'][1]['amount_minor']); // 9%
    }

    public function test_india_gst_inter_state_applies_igst(): void
    {
        $result = $this->taxEngine->calculate([
            'tenant_id' => 'tenant_test_1',
            'jurisdiction' => 'IN',
            'currency' => 'INR',
            'supplier_state' => 'MH',
            'customer_state' => 'DL', // Maharashtra to Delhi
            'lines' => [
                [
                    'sku' => 'satyanarayan-puja',
                    'quantity' => 2,
                    'unit_price_minor' => 10000, // ₹200.00 total
                ],
            ],
        ]);

        $this->assertSame(20000, $result['taxable_amount_minor']);
        $this->assertSame(3600, $result['tax_amount_minor']); // 18% IGST = ₹36.00
        $this->assertSame(23600, $result['total_amount_minor']);

        $line = $result['lines'][0];
        $this->assertCount(1, $line['components']);
        $this->assertSame('IGST', $line['components'][0]['name']);
        $this->assertSame(3600, $line['components'][0]['amount_minor']);
    }

    public function test_india_gst_exempt_sac_code(): void
    {
        $result = $this->taxEngine->calculate([
            'tenant_id' => 'tenant_test_1',
            'jurisdiction' => 'IN',
            'currency' => 'INR',
            'lines' => [
                [
                    'sku' => 'sacred-offering',
                    'quantity' => 1,
                    'unit_price_minor' => 5000,
                    'tax_code' => 'SAC-EXEMPT',
                ],
            ],
        ]);

        $this->assertSame(5000, $result['taxable_amount_minor']);
        $this->assertSame(0, $result['tax_amount_minor']);
        $this->assertSame(5000, $result['total_amount_minor']);
    }

    public function test_united_states_sales_tax_california_and_delaware(): void
    {
        // California: combined state + local
        $caResult = $this->taxEngine->calculate([
            'tenant_id' => 'tenant_test_1',
            'jurisdiction' => 'US',
            'currency' => 'USD',
            'customer_state' => 'CA',
            'lines' => [
                [
                    'sku' => 'puja-service',
                    'quantity' => 1,
                    'unit_price_minor' => 10000, // $100.00
                ],
            ],
        ]);

        $this->assertSame(10000, $caResult['taxable_amount_minor']);
        $this->assertSame(885, $caResult['tax_amount_minor']); // 8.85% = $8.85
        $this->assertSame(10885, $caResult['total_amount_minor']);

        // Delaware: 0% sales tax state
        $deResult = $this->taxEngine->calculate([
            'tenant_id' => 'tenant_test_1',
            'jurisdiction' => 'US',
            'currency' => 'USD',
            'customer_state' => 'DE',
            'lines' => [
                [
                    'sku' => 'puja-service',
                    'quantity' => 1,
                    'unit_price_minor' => 10000,
                ],
            ],
        ]);

        $this->assertSame(10000, $deResult['taxable_amount_minor']);
        $this->assertSame(0, $deResult['tax_amount_minor']);
        $this->assertSame(10000, $deResult['total_amount_minor']);
    }

    public function test_united_kingdom_vat_standard_and_reduced(): void
    {
        $gbResult = $this->taxEngine->calculate([
            'tenant_id' => 'tenant_test_1',
            'jurisdiction' => 'GB',
            'currency' => 'GBP',
            'lines' => [
                [
                    'sku' => 'standard-service',
                    'quantity' => 1,
                    'unit_price_minor' => 10000, // £100.00 standard 20%
                    'tax_code' => 'GB-VAT-STANDARD',
                ],
                [
                    'sku' => 'reduced-goods',
                    'quantity' => 1,
                    'unit_price_minor' => 10000, // £100.00 reduced 5%
                    'tax_code' => 'GB-VAT-REDUCED',
                ],
            ],
        ]);

        $this->assertSame(20000, $gbResult['taxable_amount_minor']);
        $this->assertSame(2500, $gbResult['tax_amount_minor']); // 2000 + 500 = £25.00
        $this->assertSame(22500, $gbResult['total_amount_minor']);
    }

    public function test_united_arab_emirates_vat_and_designated_zone(): void
    {
        // Mainland UAE: 5% VAT
        $mainlandResult = $this->taxEngine->calculate([
            'tenant_id' => 'tenant_test_1',
            'jurisdiction' => 'AE',
            'currency' => 'AED',
            'is_designated_zone' => false,
            'lines' => [
                [
                    'sku' => 'dubai-puja',
                    'quantity' => 1,
                    'unit_price_minor' => 20000, // AED 200.00
                ],
            ],
        ]);

        $this->assertSame(20000, $mainlandResult['taxable_amount_minor']);
        $this->assertSame(1000, $mainlandResult['tax_amount_minor']); // 5% = AED 10.00
        $this->assertSame(21000, $mainlandResult['total_amount_minor']);

        // Designated Free Zone: 0% VAT
        $zoneResult = $this->taxEngine->calculate([
            'tenant_id' => 'tenant_test_1',
            'jurisdiction' => 'AE',
            'currency' => 'AED',
            'is_designated_zone' => true,
            'lines' => [
                [
                    'sku' => 'dubai-puja',
                    'quantity' => 1,
                    'unit_price_minor' => 20000,
                ],
            ],
        ]);

        $this->assertSame(20000, $zoneResult['taxable_amount_minor']);
        $this->assertSame(0, $zoneResult['tax_amount_minor']);
        $this->assertSame(20000, $zoneResult['total_amount_minor']);
    }
}
