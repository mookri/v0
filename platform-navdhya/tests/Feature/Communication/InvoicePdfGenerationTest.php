<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Financial\FinancialInvoiceService;
use App\Domain\Delivery\Financial\InvoicePdfService;
use App\Domain\Delivery\Models\FinancialInvoice;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM07-03:
 * Proves that invoice PDF rendering adheres strictly to Sacred Ember tokens with zero green.
 */
final class InvoicePdfGenerationTest extends TenantIntegrationTestCase
{
    public function test_invoice_pdf_and_html_rendering_adhere_to_sacred_ember_tokens(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $invoiceService = app(FinancialInvoiceService::class);
        $invoice = $invoiceService->issueInvoice(
            tenantId: $tenantId,
            bookingId: 'bkg_pdf_001',
            userId: 9201,
            supplierModel: FinancialInvoice::MODEL_FACILITATOR,
            supplierGstin: '29AABCN1234F1Z5',
            placeOfSupply: '29-Karnataka',
            taxableAmountCents: 1000000,
            cgstCents: 90000,
            sgstCents: 90000,
            igstCents: 0,
            snapshotData: [
                'items' => [
                    ['name' => 'Maha Ganapati Havan', 'sac' => '999799', 'amount' => 10000.00],
                ],
            ]
        );

        $pdfService = new InvoicePdfService();
        $html = $pdfService->renderHtml($invoice);

        // Verify Sacred Ember brand tokens
        $this->assertStringContainsString('#FFF7ED', $html); // Warm Ivory
        $this->assertStringContainsString('#2C2033', $html); // Aubergine Ink
        $this->assertStringContainsString('#F16A24', $html); // Navdhya Flame
        $this->assertStringContainsString('#EFE3D3', $html); // Soft Sand

        // Strictly zero green tokens
        $forbiddenGreens = ['green', 'emerald', 'lime', 'teal', '#10b981', '#22c55e', '#84cc16'];
        foreach ($forbiddenGreens as $green) {
            $this->assertStringNotContainsStringIgnoringCase($green, $html);
        }

        // Verify PDF byte header
        $pdfBytes = $pdfService->generatePdfBytes($invoice);
        $this->assertStringStartsWith('%PDF-1.4', $pdfBytes);
    }
}

