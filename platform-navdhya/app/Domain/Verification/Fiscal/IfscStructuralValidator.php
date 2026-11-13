<?php

namespace App\Domain\Verification\Fiscal;

/**
 * Pure-PHP structural IFSC & RBI bank account length validator (T-TR05-05.2, mode=native).
 */
final class IfscStructuralValidator
{
    /** @var array<string, string> */
    private const KNOWN_RBI_BANK_PREFIXES = [
        'SBIN' => 'State Bank of India',
        'HDFC' => 'HDFC Bank',
        'ICIC' => 'ICICI Bank',
        'AXIS' => 'Axis Bank',
        'UTIB' => 'Axis Bank',
        'PUNB' => 'Punjab National Bank',
        'CNRB' => 'Canara Bank',
        'BARB' => 'Bank of Baroda',
        'KKBK' => 'Kotak Mahindra Bank',
        'IDIB' => 'Indian Bank',
        'UBIN' => 'Union Bank of India',
        'IOBA' => 'Indian Overseas Bank',
        'YESB' => 'Yes Bank',
        'INDB' => 'IndusInd Bank',
        'RATN' => 'RBL Bank',
        'BKID' => 'Bank of India',
        'CBIN' => 'Central Bank of India',
        'FDRL' => 'Federal Bank',
        'AIRP' => 'Airtel Payments Bank',
        'PYTM' => 'Paytm Payments Bank',
    ];

    /**
     * @return array{valid: bool, ifsc: string, bank_name: string|null, reason_codes: list<string>}
     */
    public function validate(string $rawIfsc, string $rawAccountNumber): array
    {
        $ifsc = strtoupper(trim($rawIfsc));
        $account = preg_replace('/\s+/', '', trim($rawAccountNumber)) ?? '';
        $reasons = [];

        if (preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc) !== 1) {
            $reasons[] = 'INVALID_IFSC_FORMAT';
        }

        $prefix = substr($ifsc, 0, 4);
        $bankName = self::KNOWN_RBI_BANK_PREFIXES[$prefix] ?? null;

        if (preg_match('/^[0-9]{9,18}$/', $account) !== 1) {
            $reasons[] = 'INVALID_ACCOUNT_NUMBER_LENGTH';
        }

        return [
            'valid' => $reasons === [],
            'ifsc' => $ifsc,
            'bank_name' => $bankName,
            'reason_codes' => $reasons,
        ];
    }
}

