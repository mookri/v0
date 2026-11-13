<?php

namespace Tests\Feature\Communication;

use App\Domain\Delivery\Events\PaymentLifecycleEvents;
use App\Domain\Delivery\Financial\FinancialInvoiceService;
use App\Domain\Delivery\Financial\InvoiceParticularsService;
use App\Domain\Delivery\Listeners\PaymentLifecycleListener;
use App\Domain\Delivery\Models\FinancialInvoice;
use App\Domain\Delivery\Models\NotificationOutbox;
use Tests\TenantIntegrationTestCase;

/**
 * Tests Task T-CM09-02:
 * Payment lifecycle events, financial invoice issuance integration,
 * and semantic separation of payment receipts from ritual confirmations.
 */
final class PaymentEventProducerTest extends TenantIntegrationTestCase
{
    public function test_payment_received_issues_invoice_and_enqueues_receipt_notification(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $invoiceService = new FinancialInvoiceService(new InvoiceParticularsService());
        $listener = new PaymentLifecycleListener($invoiceService);

        $outbox = $listener->handle(
            tenantId: $tenantId,
            eventType: PaymentLifecycleEvents::PAYMENT_RECEIVED,
            paymentId: 'pay_test_888',
            bookingId: 'book_999',
            userId: 123,
            payload: [
                'amount_cents' => 118000,
                'taxable_cents' => 100000,
                'cgst_cents' => 9000,
                'sgst_cents' => 9000,
            ]
        );

        $this->assertInstanceOf(NotificationOutbox::class, $outbox);
        $this->assertSame(PaymentLifecycleEvents::PAYMENT_RECEIVED, $outbox->event_type);
        $this->assertSame('payment_receipt', $outbox->payload_json['template_kind']);
        $this->assertTrue($outbox->payload_json['is_financial_receipt_only']);

        $invoiceNumber = $outbox->payload_json['invoice_number'];
        $this->assertNotNull($invoiceNumber);
        $this->assertStringStartsWith('INV-', $invoiceNumber);

        // Verify invoice was saved in financial_invoices table
        $invoice = FinancialInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('invoice_number', $invoiceNumber)
            ->first();

        $this->assertNotNull($invoice);
        $this->assertSame(118000, $invoice->total_amount_cents);
    }

    public function test_payment_failed_enqueues_alert_without_issuing_invoice(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);
        $tenantId = (string) $tenant->getKey();

        $invoiceService = new FinancialInvoiceService(new InvoiceParticularsService());
        $listener = new PaymentLifecycleListener($invoiceService);

        $outbox = $listener->handle(
            tenantId: $tenantId,
            eventType: PaymentLifecycleEvents::PAYMENT_FAILED,
            paymentId: 'pay_fail_777',
            bookingId: 'book_999',
            userId: 123,
            payload: [
                'reason' => 'insufficient_funds',
            ]
        );

        $this->assertInstanceOf(NotificationOutbox::class, $outbox);
        $this->assertSame(PaymentLifecycleEvents::PAYMENT_FAILED, $outbox->event_type);
        $this->assertSame('payment_failed', $outbox->payload_json['template_kind']);
        $this->assertNull($outbox->payload_json['invoice_number']);

        // Verify no invoice was created
        $this->assertSame(0, FinancialInvoice::query()->where('tenant_id', $tenantId)->count());
    }
}

