<?php

namespace App\Modules\Finance\Domain;

use App\Modules\Finance\Models\FinanceDocument;
use App\Modules\Finance\Models\FinanceDocumentLine;
use App\Modules\Finance\Models\LedgerEntry;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use Carbon\CarbonImmutable;

final class FinanceDocumentService
{
    public function __construct(
        private readonly GaplessSequenceService $sequenceService,
        private readonly DoubleEntryLedgerService $ledgerService,
        private readonly SettlementObligationService $settlementService,
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    /**
     * Issues a gapless legal tax invoice for a confirmed customer booking,
     * posts a balanced double-entry subledger journal, and creates settlement obligations.
     *
     * @param list<array{
     *     sku?: string|null,
     *     description: string,
     *     quantity: int,
     *     unit_price_minor: int,
     *     tax_rate_bps: int,
     *     tax_minor: int,
     *     total_minor: int
     * }> $lines
     */
    public function issueBookingInvoice(
        string $tenantId,
        string $bookingId,
        ?string $paymentOperationId,
        int $buyerId,
        string $currency,
        int $subtotalMinor,
        int $taxMinor,
        int $totalMinor,
        array $lines,
        ?string $beneficiaryType = null,
        ?string $beneficiaryId = null,
        int $beneficiaryPayoutMinor = 0,
        int $tdsRateBps = 0,
        ?CarbonImmutable $issuedAt = null
    ): FinanceDocument {
        $now = $issuedAt ?? CarbonImmutable::now('UTC');
        $invoiceNumber = $this->sequenceService->nextNumber($tenantId, 'INV', (int) $now->format('Y'));

        $document = new FinanceDocument();
        $document->forceFill([
            'tenant_id' => $tenantId,
            'document_number' => $invoiceNumber,
            'document_type' => FinanceDocument::TYPE_INVOICE,
            'booking_id' => $bookingId,
            'payment_operation_id' => $paymentOperationId,
            'buyer_id' => $buyerId,
            'currency' => $currency,
            'subtotal_minor' => $subtotalMinor,
            'tax_minor' => $taxMinor,
            'total_minor' => $totalMinor,
            'status' => FinanceDocument::STATUS_ISSUED,
            'issued_at' => $now,
        ]);
        $document->save();

        $lineNumber = 1;
        foreach ($lines as $line) {
            $lineRow = new FinanceDocumentLine();
            $lineRow->forceFill([
                'tenant_id' => $tenantId,
                'finance_document_id' => (string) $document->getKey(),
                'line_number' => $lineNumber++,
                'sku' => $line['sku'] ?? null,
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price_minor' => $line['unit_price_minor'],
                'tax_rate_bps' => $line['tax_rate_bps'],
                'tax_minor' => $line['tax_minor'],
                'total_minor' => $line['total_minor'],
            ]);
            $lineRow->save();
        }

        // Post balanced double-entry subledger journal
        $journalEntries = [
            [
                'account_code' => '1010', // Gateway Clearing (Asset Debit)
                'entry_direction' => LedgerEntry::DIRECTION_DEBIT,
                'amount_minor' => $totalMinor,
                'currency' => $currency,
            ],
        ];

        if ($taxMinor > 0) {
            $journalEntries[] = [
                'account_code' => '2100', // Tax Payable (Liability Credit)
                'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                'amount_minor' => $taxMinor,
                'currency' => $currency,
            ];
        }

        if ($beneficiaryPayoutMinor > 0) {
            $journalEntries[] = [
                'account_code' => '2020', // Merchant / Pandit Payable (Liability Credit)
                'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                'amount_minor' => $beneficiaryPayoutMinor,
                'currency' => $currency,
            ];
        }

        $platformRevenueMinor = $totalMinor - $taxMinor - $beneficiaryPayoutMinor;
        if ($platformRevenueMinor > 0) {
            $journalEntries[] = [
                'account_code' => '4010', // Platform Service Revenue (Revenue Credit)
                'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                'amount_minor' => $platformRevenueMinor,
                'currency' => $currency,
            ];
        }

        $this->ledgerService->postJournal(
            $tenantId,
            'finance_document',
            (string) $document->getKey(),
            "Customer Booking Invoice {$invoiceNumber}",
            $journalEntries,
            $now
        );

        // Generate beneficiary settlement obligation if applicable
        if ($beneficiaryType !== null && $beneficiaryId !== null && $beneficiaryPayoutMinor > 0) {
            $this->settlementService->createObligation(
                $tenantId,
                $beneficiaryType,
                $beneficiaryId,
                $bookingId,
                $beneficiaryPayoutMinor,
                0, // Commission already deducted from payout
                $tdsRateBps,
                $currency
            );
        }

        $this->outbox->append(
            $tenantId,
            'finance_document',
            (string) $document->getKey(),
            'finance.document.issued',
            [
                'document_id' => (string) $document->getKey(),
                'document_number' => $invoiceNumber,
                'booking_id' => $bookingId,
                'total_minor' => $totalMinor,
                'currency' => $currency,
            ]
        );

        $this->audit->record(
            'finance.document.issued',
            ['module' => 'finance', 'resource_type' => 'finance_document', 'resource_id' => (string) $document->getKey()],
            null,
            ['document_number' => $invoiceNumber, 'total_minor' => $totalMinor, 'currency' => $currency]
        );

        return $document;
    }

    /**
     * Issues a gapless legal credit note for a cancelled or refunded booking.
     */
    public function issueCreditNote(
        string $tenantId,
        string $originalInvoiceNumber,
        string $bookingId,
        int $buyerId,
        string $currency,
        int $subtotalMinor,
        int $taxMinor,
        int $totalMinor,
        ?CarbonImmutable $issuedAt = null
    ): FinanceDocument {
        $now = $issuedAt ?? CarbonImmutable::now('UTC');
        $creditNoteNumber = $this->sequenceService->nextNumber($tenantId, 'CN', (int) $now->format('Y'));

        $document = new FinanceDocument();
        $document->forceFill([
            'tenant_id' => $tenantId,
            'document_number' => $creditNoteNumber,
            'document_type' => FinanceDocument::TYPE_CREDIT_NOTE,
            'booking_id' => $bookingId,
            'buyer_id' => $buyerId,
            'currency' => $currency,
            'subtotal_minor' => $subtotalMinor,
            'tax_minor' => $taxMinor,
            'total_minor' => $totalMinor,
            'status' => FinanceDocument::STATUS_ISSUED,
            'issued_at' => $now,
        ]);
        $document->save();

        $journalEntries = [
            [
                'account_code' => '1010', // Gateway Clearing / Refund Outflow (Credit)
                'entry_direction' => LedgerEntry::DIRECTION_CREDIT,
                'amount_minor' => $totalMinor,
                'currency' => $currency,
            ],
        ];

        if ($taxMinor > 0) {
            $journalEntries[] = [
                'account_code' => '2100', // Tax Payable Reversal (Debit)
                'entry_direction' => LedgerEntry::DIRECTION_DEBIT,
                'amount_minor' => $taxMinor,
                'currency' => $currency,
            ];
        }

        $revenueReversalMinor = $totalMinor - $taxMinor;
        if ($revenueReversalMinor > 0) {
            $journalEntries[] = [
                'account_code' => '4010', // Revenue Reversal (Debit)
                'entry_direction' => LedgerEntry::DIRECTION_DEBIT,
                'amount_minor' => $revenueReversalMinor,
                'currency' => $currency,
            ];
        }

        $this->ledgerService->postJournal(
            $tenantId,
            'finance_document',
            (string) $document->getKey(),
            "Customer Credit Note {$creditNoteNumber} against {$originalInvoiceNumber}",
            $journalEntries,
            $now
        );

        $this->outbox->append(
            $tenantId,
            'finance_document',
            (string) $document->getKey(),
            'finance.document.credit_note_issued',
            [
                'document_id' => (string) $document->getKey(),
                'document_number' => $creditNoteNumber,
                'original_invoice_number' => $originalInvoiceNumber,
                'booking_id' => $bookingId,
                'total_minor' => $totalMinor,
                'currency' => $currency,
            ]
        );

        return $document;
    }
}
