<?php

namespace Tests\Unit\Communication;

use App\Domain\Delivery\Financial\InvoiceParticularsService;
use Carbon\CarbonImmutable;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM07-01:
 * Validates GST Rule 46 statutory requirements, tax arithmetic, and financial year detection.
 */
final class InvoiceParticularsTest extends TenantIntegrationTestCase
{
    public function test_rule46_validation_passes_valid_invoice_particulars(): void
    {
        $service = new InvoiceParticularsService();

        $validParticulars = [
            'supplier_name' => 'Navdhya Sacred Ritual Services Pvt Ltd',
            'supplier_address' => 'Indiranagar, Bangalore, Karnataka 560038',
            'supplier_gstin' => '29AABCN1234F1Z5',
            'invoice_number' => 'INV-2026-27-000001',
            'invoice_date' => '2026-11-15',
            'recipient_name' => 'Amit Sharma',
            'recipient_address' => 'Koramangala, Bangalore',
            'place_of_supply' => '29-Karnataka',
            'sac_code' => '999799',
            'taxable_value' => 500000,
            'cgst' => 45000,
            'sgst' => 45000,
            'igst' => 0,
            'total_amount' => 590000,
        ];

        $result = $service->validateRule46($validParticulars);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_rule46_validation_catches_missing_fields_and_tax_mismatch(): void
    {
        $service = new InvoiceParticularsService();

        $invalidParticulars = [
            'supplier_name' => 'Navdhya',
            'supplier_gstin' => 'SHORT_GST', // Invalid length
            'taxable_value' => 100000,
            'cgst' => 9000,
            'sgst' => 9000,
            'igst' => 0,
            'total_amount' => 150000, // Arithmetic mismatch!
        ];

        $result = $service->validateRule46($invalidParticulars);
        $this->assertFalse($result['valid']);
        $this->assertGreaterThanOrEqual(3, count($result['errors']));
    }

    public function test_financial_year_detection(): void
    {
        $service = new InvoiceParticularsService();

        // November 2026 -> FY 2026-27
        $nov2026 = CarbonImmutable::parse('2026-11-15');
        $this->assertSame('2026-27', $service->currentFinancialYear($nov2026));

        // February 2027 -> FY 2026-27
        $feb2027 = CarbonImmutable::parse('2027-02-10');
        $this->assertSame('2026-27', $service->currentFinancialYear($feb2027));

        // April 2027 -> FY 2027-28
        $apr2027 = CarbonImmutable::parse('2027-04-01');
        $this->assertSame('2027-28', $service->currentFinancialYear($apr2027));
    }
}

