<?php

namespace App\Modules\Tax\Domain\Jurisdictions;

final class UnitedKingdomVatCalculator implements JurisdictionCalculatorInterface
{
    public function calculate(array $request): array
    {
        $currency = $request['currency'] ?? 'GBP';

        $computedLines = [];
        $totalTaxable = 0;
        $totalTax = 0;
        $summaryComponents = [];

        foreach ($request['lines'] as $line) {
            $quantity = (int) ($line['quantity'] ?? 1);
            $unitPrice = (int) ($line['unit_price_minor'] ?? 0);
            $discount = (int) ($line['discount_minor'] ?? 0);
            $taxCode = strtoupper(trim((string) ($line['tax_code'] ?? 'GB-VAT-STANDARD')));

            $taxableMinor = max(0, ($quantity * $unitPrice) - $discount);

            $rateBps = match ($taxCode) {
                'GB-VAT-REDUCED' => 500, // 5.00%
                'GB-VAT-ZERO', 'GB-VAT-EXEMPT', 'EXEMPT' => 0, // 0.00%
                default => 2000, // 20.00% standard rate
            };

            $components = [];
            $lineTaxMinor = 0;

            if ($rateBps > 0) {
                $lineTaxMinor = (int) intdiv($taxableMinor * $rateBps + 5000, 10000);
                $components[] = [
                    'name' => 'UK VAT',
                    'rate_basis_points' => $rateBps,
                    'amount_minor' => $lineTaxMinor,
                ];
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
                'rate_basis_points' => $rateBps,
                'components' => $components,
            ];
        }

        return [
            'jurisdiction' => 'GB',
            'currency' => $currency,
            'taxable_amount_minor' => $totalTaxable,
            'tax_amount_minor' => $totalTax,
            'total_amount_minor' => $totalTaxable + $totalTax,
            'lines' => $computedLines,
            'summary_components' => array_values($summaryComponents),
        ];
    }
}
