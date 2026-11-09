<?php

namespace App\Modules\Tax\Domain\Jurisdictions;

final class UnitedStatesSalesTaxCalculator implements JurisdictionCalculatorInterface
{
    /**
     * State and Local combined rates in basis points (State, Local)
     *
     * @var array<string, array{state: int, local: int}>
     */
    private const STATE_RATES = [
        'AL' => ['state' => 400, 'local' => 524], // 9.24%
        'AK' => ['state' => 0, 'local' => 176],   // 1.76% (No state tax)
        'AZ' => ['state' => 560, 'local' => 280], // 8.40%
        'AR' => ['state' => 650, 'local' => 297], // 9.47%
        'CA' => ['state' => 600, 'local' => 285], // 8.85%
        'CO' => ['state' => 290, 'local' => 487], // 7.77%
        'CT' => ['state' => 635, 'local' => 0],   // 6.35%
        'DE' => ['state' => 0, 'local' => 0],     // 0%
        'DC' => ['state' => 600, 'local' => 0],   // 6.00%
        'FL' => ['state' => 600, 'local' => 102], // 7.02%
        'GA' => ['state' => 400, 'local' => 335], // 7.35%
        'HI' => ['state' => 400, 'local' => 44],  // 4.44%
        'ID' => ['state' => 600, 'local' => 3],   // 6.03%
        'IL' => ['state' => 625, 'local' => 256], // 8.81%
        'IN' => ['state' => 700, 'local' => 0],   // 7.00%
        'IA' => ['state' => 600, 'local' => 94],  // 6.94%
        'KS' => ['state' => 650, 'local' => 220], // 8.70%
        'KY' => ['state' => 600, 'local' => 0],   // 6.00%
        'LA' => ['state' => 445, 'local' => 510], // 9.55%
        'ME' => ['state' => 550, 'local' => 0],   // 5.50%
        'MD' => ['state' => 600, 'local' => 0],   // 6.00%
        'MA' => ['state' => 625, 'local' => 0],   // 6.25%
        'MI' => ['state' => 600, 'local' => 0],   // 6.00%
        'MN' => ['state' => 688, 'local' => 61],  // 7.49%
        'MS' => ['state' => 700, 'local' => 7],   // 7.07%
        'MO' => ['state' => 423, 'local' => 413], // 8.36%
        'MT' => ['state' => 0, 'local' => 0],     // 0%
        'NE' => ['state' => 550, 'local' => 144], // 6.94%
        'NV' => ['state' => 685, 'local' => 138], // 8.23%
        'NH' => ['state' => 0, 'local' => 0],     // 0%
        'NJ' => ['state' => 663, 'local' => 0],   // 6.63%
        'NM' => ['state' => 488, 'local' => 284], // 7.72%
        'NY' => ['state' => 400, 'local' => 452], // 8.52%
        'NC' => ['state' => 475, 'local' => 225], // 7.00%
        'ND' => ['state' => 500, 'local' => 204], // 7.04%
        'OH' => ['state' => 575, 'local' => 149], // 7.24%
        'OK' => ['state' => 450, 'local' => 448], // 8.98%
        'OR' => ['state' => 0, 'local' => 0],     // 0%
        'PA' => ['state' => 600, 'local' => 34],  // 6.34%
        'RI' => ['state' => 700, 'local' => 0],   // 7.00%
        'SC' => ['state' => 600, 'local' => 144], // 7.44%
        'SD' => ['state' => 420, 'local' => 191], // 6.11%
        'TN' => ['state' => 700, 'local' => 255], // 9.55%
        'TX' => ['state' => 625, 'local' => 195], // 8.20%
        'UT' => ['state' => 610, 'local' => 115], // 7.25%
        'VT' => ['state' => 600, 'local' => 36],  // 6.36%
        'VA' => ['state' => 530, 'local' => 45],  // 5.75%
        'WA' => ['state' => 650, 'local' => 279], // 9.29%
        'WV' => ['state' => 600, 'local' => 55],  // 6.55%
        'WI' => ['state' => 500, 'local' => 43],  // 5.43%
        'WY' => ['state' => 400, 'local' => 136], // 5.36%
    ];

    public function calculate(array $request): array
    {
        $currency = $request['currency'] ?? 'USD';
        $state = strtoupper(trim((string) ($request['customer_state'] ?? $request['supplier_state'] ?? 'CA')));

        $rateData = self::STATE_RATES[$state] ?? ['state' => 600, 'local' => 0];
        $stateRateBps = $rateData['state'];
        $localRateBps = $rateData['local'];
        $combinedRateBps = $stateRateBps + $localRateBps;

        $computedLines = [];
        $totalTaxable = 0;
        $totalTax = 0;
        $summaryComponents = [];

        foreach ($request['lines'] as $line) {
            $quantity = (int) ($line['quantity'] ?? 1);
            $unitPrice = (int) ($line['unit_price_minor'] ?? 0);
            $discount = (int) ($line['discount_minor'] ?? 0);
            $taxCode = (string) ($line['tax_code'] ?? '');

            $taxableMinor = max(0, ($quantity * $unitPrice) - $discount);
            $isExempt = in_array(strtoupper($taxCode), ['US-EXEMPT', 'EXEMPT'], true);

            $components = [];
            $lineTaxMinor = 0;

            if (! $isExempt && $combinedRateBps > 0) {
                $stateTaxMinor = (int) intdiv($taxableMinor * $stateRateBps + 5000, 10000);
                $localTaxMinor = (int) intdiv($taxableMinor * $localRateBps + 5000, 10000);
                $lineTaxMinor = $stateTaxMinor + $localTaxMinor;

                if ($stateTaxMinor > 0) {
                    $components[] = [
                        'name' => "US State Sales Tax ({$state})",
                        'rate_basis_points' => $stateRateBps,
                        'amount_minor' => $stateTaxMinor,
                    ];
                }
                if ($localTaxMinor > 0) {
                    $components[] = [
                        'name' => "US Local Sales Tax ({$state})",
                        'rate_basis_points' => $localRateBps,
                        'amount_minor' => $localTaxMinor,
                    ];
                }
            }

            $lineTotalMinor = $taxableMinor + $lineTaxMinor;
            $totalTaxable += $taxableMinor;
            $totalTax += $lineTaxMinor;

            foreach ($components as $comp) {
                $name = $comp['name'];
                if (! isset($summaryComponents[$name])) {
                    $summaryComponents[$name] = [
                        'name' => $name,
                        'rate_basis_points' => $comp['rate_basis_points'],
                        'amount_minor' => 0,
                    ];
                }
                $summaryComponents[$name]['amount_minor'] += $comp['amount_minor'];
            }

            $computedLines[] = [
                'sku' => (string) ($line['sku'] ?? ''),
                'taxable_minor' => $taxableMinor,
                'tax_minor' => $lineTaxMinor,
                'total_minor' => $lineTotalMinor,
                'rate_basis_points' => $combinedRateBps,
                'components' => $components,
            ];
        }

        return [
            'jurisdiction' => 'US',
            'currency' => $currency,
            'taxable_amount_minor' => $totalTaxable,
            'tax_amount_minor' => $totalTax,
            'total_amount_minor' => $totalTaxable + $totalTax,
            'lines' => $computedLines,
            'summary_components' => array_values($summaryComponents),
        ];
    }
}
