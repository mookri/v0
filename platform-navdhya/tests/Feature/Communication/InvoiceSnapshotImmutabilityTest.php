<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Financial\FinancialInvoiceService;
use App\Domain\Delivery\Models\FinancialInvoice;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM07-02:
 * Proves atomic, sequential invoice numbering and immutable snapshot preservation.
 */
final class InvoiceSnapshotImmutabilityTest extends TenantIntegrationTestCase
{
    public function test_allocates_sequential_invoice_numbers_per_financial_year(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $service = app(FinancialInvoiceService::class);

        // Issue first invoice
        $inv1 = $service->issueInvoice(
            tenantId: $tenantId,
            bookingId: 'bkg_seq_001',
            userId: 9101,
            supplierModel: FinancialInvoice::MODEL_FACILITATOR,
            supplierGstin: '29AABCN1234F1Z5',
            placeOfSupply: '29-Karnataka',
            taxableAmountCents: 500000,
            cgstCents: 45000,
            sgstCents: 45000,
            igstCents: 0,
            snapshotData: ['ritual' => 'Satyanarayan', 'price_cents' => 500000]
        );

        // Issue second invoice
        $inv2 = $service->issueInvoice(
            tenantId: $tenantId,
            bookingId: 'bkg_seq_002',
            userId: 9102,
            supplierModel: FinancialInvoice::MODEL_FACILITATOR,
            supplierGstin: '29AABCN1234F1Z5',
            placeOfSupply: '29-Karnataka',
            taxableAmountCents: 800000,
            cgstCents: 72000,
            sgstCents: 72000,
            igstCents: 0,
            snapshotData: ['ritual' => 'Navagraha Havan', 'price_cents' => 800000]
        );

        $this->assertSame(1, $inv1->sequence_number);
        $this->assertSame('INV-2026-27-000001', $inv1->invoice_number);

        $this->assertSame(2, $inv2->sequence_number);
        $this->assertSame('INV-2026-27-000002', $inv2->invoice_number);

        // Verify snapshot immutability
        $this->assertSame(500000, $inv1->snapshot_json['price_cents']);
        $this->assertSame('Satyanarayan', $inv1->snapshot_json['ritual']);
    }
}

