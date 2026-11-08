<?php

namespace App\Modules\Tax\Domain;

use App\Modules\Tax\Contracts\CalculatesTax;
use App\Modules\Tax\Domain\Jurisdictions\IndiaGstCalculator;
use App\Modules\Tax\Domain\Jurisdictions\JurisdictionCalculatorInterface;
use App\Modules\Tax\Domain\Jurisdictions\UnitedArabEmiratesVatCalculator;
use App\Modules\Tax\Domain\Jurisdictions\UnitedKingdomVatCalculator;
use App\Modules\Tax\Domain\Jurisdictions\UnitedStatesSalesTaxCalculator;
use App\Modules\Tax\Models\TaxComputation;
use InvalidArgumentException;

final class TaxEngine implements CalculatesTax
{
    /**
     * @var array<string, JurisdictionCalculatorInterface>
     */
    private array $calculators;

    public function __construct(
        IndiaGstCalculator $inCalc,
        UnitedStatesSalesTaxCalculator $usCalc,
        UnitedKingdomVatCalculator $gbCalc,
        UnitedArabEmiratesVatCalculator $aeCalc,
    ) {
        $this->calculators = [
            'IN' => $inCalc,
            'US' => $usCalc,
            'GB' => $gbCalc,
            'AE' => $aeCalc,
        ];
    }

    public function calculate(array $request): array
    {
        $jurisdiction = strtoupper(trim((string) ($request['jurisdiction'] ?? 'IN')));

        $calculator = $this->calculators[$jurisdiction] ?? null;
        if ($calculator === null) {
            throw new InvalidArgumentException("Unsupported tax jurisdiction: {$jurisdiction}");
        }

        return $calculator->calculate($request);
    }

    /**
     * Persists or updates an immutable tax computation snapshot.
     *
     * @param array<string, mixed> $calculationResult
     */
    public function recordComputation(
        string $tenantId,
        string $jurisdiction,
        array $calculationResult,
        ?string $quoteId = null,
        ?string $bookingId = null,
        ?string $orderId = null,
        string $status = TaxComputation::STATUS_QUOTED
    ): TaxComputation {
        $computation = new TaxComputation();
        $computation->forceFill([
            'tenant_id' => $tenantId,
            'quote_id' => $quoteId,
            'booking_id' => $bookingId,
            'order_id' => $orderId,
            'jurisdiction' => $jurisdiction,
            'currency' => $calculationResult['currency'],
            'taxable_amount_minor' => (int) $calculationResult['taxable_amount_minor'],
            'tax_amount_minor' => (int) $calculationResult['tax_amount_minor'],
            'total_amount_minor' => (int) $calculationResult['total_amount_minor'],
            'components_json' => [
                'lines' => $calculationResult['lines'],
                'summary_components' => $calculationResult['summary_components'],
            ],
            'status' => $status,
        ]);
        $computation->save();

        return $computation;
    }
}
