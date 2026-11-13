<?php

namespace App\Domain\Delivery\Financial;

use DateTimeInterface;

/**
 * GST Rule 46 Tax Invoice particulars validation service (E-CM-07 / Task T-CM07-01).
 * Enforces mandatory statutory invoice fields under Indian GST law.
 */
final class InvoiceParticularsService
{
    private const MANDATORY_FIELDS = [
        'supplier_name',
        'supplier_address',
        'supplier_gstin',
        'invoice_number',
        'invoice_date',
        'recipient_name',
        'recipient_address',
        'place_of_supply',
        'sac_code',
        'taxable_value',
        'total_amount',
    ];

    /**
     * @param array<string, mixed> $particulars
     * @return array{valid: bool, errors: list<string>}
     */
    public function validateRule46(array $particulars): array
    {
        $errors = [];

        foreach (self::MANDATORY_FIELDS as $field) {
            if (! isset($particulars[$field]) || trim((string) $particulars[$field]) === '') {
                $errors[] = "Missing mandatory GST Rule 46 particular: [{$field}].";
            }
        }

        // Validate supplier GSTIN format (15 characters)
        if (isset($particulars['supplier_gstin'])) {
            $gstin = trim((string) $particulars['supplier_gstin']);
            if (strlen($gstin) !== 15) {
                $errors[] = "Invalid supplier GSTIN length: expected 15 characters, got [{$gstin}].";
            }
        }

        // Validate tax computation
        $taxable = (int) ($particulars['taxable_value'] ?? 0);
        $cgst = (int) ($particulars['cgst'] ?? 0);
        $sgst = (int) ($particulars['sgst'] ?? 0);
        $igst = (int) ($particulars['igst'] ?? 0);
        $total = (int) ($particulars['total_amount'] ?? 0);

        if ($total !== ($taxable + $cgst + $sgst + $igst)) {
            $errors[] = "Arithmetic mismatch: total_amount must equal taxable_value + cgst + sgst + igst.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function currentFinancialYear(?DateTimeInterface $date = null): string
    {
        $dt = $date ?? now();
        $month = (int) $dt->format('n');
        $year = (int) $dt->format('Y');

        if ($month >= 4) {
            $startYear = $year;
            $endYear = ($year + 1) % 100;
        } else {
            $startYear = $year - 1;
            $endYear = $year % 100;
        }

        return sprintf('%04d-%02d', $startYear, $endYear);
    }
}

