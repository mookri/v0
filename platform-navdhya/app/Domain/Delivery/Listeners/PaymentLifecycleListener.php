<?php

namespace App\Domain\Delivery\Listeners;

use App\Domain\Delivery\Events\PaymentLifecycleEvents;
use App\Domain\Delivery\Financial\FinancialInvoiceService;
use App\Domain\Delivery\Models\FinancialInvoice;
use App\Domain\Delivery\Models\NotificationOutbox;
use App\Domain\Delivery\Support\DedupKeyGenerator;

final class PaymentLifecycleListener
{
    public function __construct(
        private readonly ?FinancialInvoiceService $invoiceService = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(
        string $tenantId,
        string $eventType,
        string $paymentId,
        string $bookingId,
        int|string $userId,
        array $payload = []
    ): ?NotificationOutbox {
        $invoiceNumber = null;

        // If payment received, issue financial invoice if requested
        if ($eventType === PaymentLifecycleEvents::PAYMENT_RECEIVED && $this->invoiceService !== null) {
            $taxableCents = (int) ($payload['taxable_cents'] ?? ($payload['amount_cents'] ?? 100000));
            $cgstCents = (int) ($payload['cgst_cents'] ?? 0);
            $sgstCents = (int) ($payload['sgst_cents'] ?? 0);
            $igstCents = (int) ($payload['igst_cents'] ?? 0);

            $invoice = $this->invoiceService->issueInvoice(
                tenantId: $tenantId,
                bookingId: $bookingId,
                userId: (int) $userId,
                supplierModel: $payload['supplier_model'] ?? 'DIRECT',
                supplierGstin: $payload['supplier_gstin'] ?? '27AAPCN1234F1Z1',
                placeOfSupply: $payload['place_of_supply'] ?? 'Maharashtra (27)',
                taxableAmountCents: $taxableCents,
                cgstCents: $cgstCents,
                sgstCents: $sgstCents,
                igstCents: $igstCents,
                snapshotData: array_merge($payload, ['payment_id' => $paymentId]),
                recipientGstin: $payload['recipient_gstin'] ?? null
            );

            $invoiceNumber = $invoice->invoice_number;
        }

        $templateKind = match ($eventType) {
            PaymentLifecycleEvents::PAYMENT_RECEIVED => 'payment_receipt',
            PaymentLifecycleEvents::PAYMENT_FAILED => 'payment_failed',
            PaymentLifecycleEvents::PAYMENT_REFUNDED => 'payment_refunded',
            default => 'payment_update',
        };

        $revision = (int) ($payload['revision'] ?? 1);
        $dedupKey = DedupKeyGenerator::generate(
            tenantId: $tenantId,
            eventType: $eventType,
            recipient: (string) $userId,
            subjectEntity: "payment:{$paymentId}",
            revision: $revision
        );

        return NotificationOutbox::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => "evt_{$eventType}_{$paymentId}_{$revision}",
            'aggregate_type' => 'payment',
            'aggregate_id' => $paymentId,
            'event_type' => $eventType,
            'payload_json' => array_merge($payload, [
                'payment_id' => $paymentId,
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'invoice_number' => $invoiceNumber,
                'template_kind' => $templateKind,
                'dedup_key' => $dedupKey,
                'is_financial_receipt_only' => true,
            ]),
            'status' => NotificationOutbox::STATUS_PENDING,
            'attempt_count' => 0,
        ]);
    }
}

