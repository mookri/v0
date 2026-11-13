<?php

namespace App\Domain\Delivery\Financial;

use App\Domain\Delivery\Models\FinancialInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Financial invoice issuing and immutable snapshot service (E-CM-07 / Task T-CM07-02).
 * Allocates atomic, gapless sequence numbers per financial year.
 */
final class FinancialInvoiceService
{
    public function __construct(
        private readonly InvoiceParticularsService $particularsService,
    ) {
    }

    /**
     * @param array<string, mixed> $snapshotData
     */
    public function issueInvoice(
        string $tenantId,
        string $bookingId,
        int $userId,
        string $supplierModel,
        string $supplierGstin,
        string $placeOfSupply,
        int $taxableAmountCents,
        int $cgstCents,
        int $sgstCents,
        int $igstCents,
        array $snapshotData,
        ?string $recipientGstin = null
    ): FinancialInvoice {
        $now = CarbonImmutable::now();
        $fyYear = $this->particularsService->currentFinancialYear($now);
        $totalAmountCents = $taxableAmountCents + $cgstCents + $sgstCents + $igstCents;

        return DB::connection('tenant_shared')->transaction(function () use (
            $tenantId,
            $bookingId,
            $userId,
            $fyYear,
            $supplierModel,
            $supplierGstin,
            $recipientGstin,
            $placeOfSupply,
            $taxableAmountCents,
            $cgstCents,
            $sgstCents,
            $igstCents,
            $totalAmountCents,
            $snapshotData,
            $now
        ): FinancialInvoice {
            $maxSeq = (int) FinancialInvoice::query()
                ->where('tenant_id', $tenantId)
                ->where('fy_year', $fyYear)
                ->lockForUpdate()
                ->max('sequence_number');

            $nextSeq = $maxSeq + 1;
            $invoiceNumber = sprintf('INV-%s-%06d', $fyYear, $nextSeq);

            return FinancialInvoice::query()->create([
                'tenant_id' => $tenantId,
                'invoice_number' => $invoiceNumber,
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'fy_year' => $fyYear,
                'sequence_number' => $nextSeq,
                'supplier_model' => $supplierModel,
                'supplier_gstin' => $supplierGstin,
                'recipient_gstin' => $recipientGstin,
                'place_of_supply' => $placeOfSupply,
                'sac_code' => '999799',
                'taxable_amount_cents' => $taxableAmountCents,
                'cgst_cents' => $cgstCents,
                'sgst_cents' => $sgstCents,
                'igst_cents' => $igstCents,
                'total_amount_cents' => $totalAmountCents,
                'snapshot_json' => $snapshotData,
                'status' => 'issued',
                'issued_at' => $now,
            ]);
        });
    }
}

