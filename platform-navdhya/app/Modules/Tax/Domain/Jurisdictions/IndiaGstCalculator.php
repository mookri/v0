<?php

namespace App\Modules\Tax\Domain\Jurisdictions;

final class IndiaGstCalculator implements JurisdictionCalculatorInterface
{
    public function calculate(array $request): array
    {
        $currency = $request['currency'] ?? 'INR';
        $supplierState = strtoupper(trim((string) ($request['supplier_state'] ?? 'MH')));
        $customerState = strtoupper(trim((string) ($request['customer_state'] ?? $supplierState)));

        $isIntraState = ($supplierState === $customerState);

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

            $isExempt = in_array(strtoupper($taxCode), ['SAC-EXEMPT', 'SAC-999722-EXEMPT', 'EXEMPT'], true);

            $components = [];
            $lineTaxMinor = 0;
            $rateBps = 0;

            if (! $isExempt) {
                if ($isIntraState) {
                    $rateBps = 1800; // 18% total (9% CGST + 9% SGST)
                    // Half-up rounding using integer math
                    $cgstMinor = (int) intdiv($taxableMinor * 900 + 5000, 10000);
                    $sgstMinor = (int) intdiv($taxableMinor * 900 + 5000, 10000);
                    $lineTaxMinor = $cgstMinor + $sgstMinor;

                    $components[] = [
                        'name' => 'CGST',
                        'rate_basis_points' => 900,
                        'amount_minor' => $cgstMinor,
                    ];
                    $components[] = [
                        'name' => 'SGST',
                        'rate_basis_points' => 900,
                        'amount_minor' => $sgstMinor,
                    ];
                } else {
                    $rateBps = 1800; // 18% IGST
                    $igstMinor = (int) intdiv($taxableMinor * 1800 + 5000, 10000);
                    $lineTaxMinor = $igstMinor;

                    $components[] = [
                        'name' => 'IGST',
                        'rate_basis_points' => 1800,
                        'amount_minor' => $igstMinor,
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
                'rate_basis_points' => $rateBps,
                'components' => $components,
            ];
        }

        return [
            'jurisdiction' => 'IN',
            'currency' => $currency,
            'taxable_amount_minor' => $totalTaxable,
            'tax_amount_minor' => $totalTax,
            'total_amount_minor' => $totalTaxable + $totalTax,
            'lines' => $computedLines,
            'summary_components' => array_values($summaryComponents),
        ];
    }
}

