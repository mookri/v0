<?php

namespace App\Modules\Finance\Domain;

use App\Modules\Finance\Models\SettlementObligation;

final class SettlementObligationService
{
    /**
     * Computes and creates a beneficiary settlement obligation with TDS withholding.
     */
    public function createObligation(
        string $tenantId,
        string $beneficiaryType,
        string $beneficiaryId,
        ?string $bookingId,
        int $grossAmountMinor,
        int $commissionMinor,
        int $tdsRateBps,
        string $currency = 'INR'
    ): SettlementObligation {
        $taxableBase = max(0, $grossAmountMinor - $commissionMinor);
        $taxWithheldMinor = $tdsRateBps > 0 ? (int) intdiv($taxableBase * $tdsRateBps + 5000, 10000) : 0;
        $netPayableMinor = max(0, $taxableBase - $taxWithheldMinor);

        $obligation = new SettlementObligation();
        $obligation->forceFill([
            'tenant_id' => $tenantId,
            'beneficiary_type' => $beneficiaryType,
            'beneficiary_id' => $beneficiaryId,
            'booking_id' => $bookingId,
            'gross_amount_minor' => $grossAmountMinor,
            'commission_minor' => $commissionMinor,
            'tax_withheld_minor' => $taxWithheldMinor,
            'net_payable_minor' => $netPayableMinor,
            'currency' => $currency,
            'status' => SettlementObligation::STATUS_PENDING,
        ]);
        $obligation->save();

        return $obligation;
    }
}

