<?php

namespace App\Domain\Verification\Fiscal;

/**
 * Pure-PHP structural GSTIN validator with per-position reason codes (T-TR05-01, mode=native).
 * Zero network calls.
 */
final class GstinStructuralValidator
{
    private const CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const VALID_STATE_CODES = [
        '01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
        '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
        '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
        '31', '32', '33', '34', '35', '36', '37', '38', '97', '99',
    ];

    private const VALID_PAN_ENTITY_CODES = ['P', 'C', 'H', 'F', 'A', 'T', 'B', 'L', 'J', 'G'];

    /**
     * @return array{valid: bool, normalized: string, state_code: string|null, reason_codes: list<string>}
     */
    public function validate(string $rawGstin): array
    {
        $gstin = strtoupper(trim($rawGstin));
        if (strlen($gstin) !== 15 || preg_match('/^[0-9A-Z]{15}$/', $gstin) !== 1) {
            return [
                'valid' => false,
                'normalized' => $gstin,
                'state_code' => null,
                'reason_codes' => ['INVALID_LENGTH_OR_CHARS'],
            ];
        }

        $reasons = [];
        $stateCode = substr($gstin, 0, 2);
        if (! in_array($stateCode, self::VALID_STATE_CODES, true)) {
            $reasons[] = 'INVALID_STATE_CODE';
        }

        $pan = substr($gstin, 2, 10);
        if (preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan) !== 1 || ! in_array($pan[3], self::VALID_PAN_ENTITY_CODES, true)) {
            $reasons[] = 'INVALID_EMBEDDED_PAN';
        }

        $entityCount = $gstin[12];
        if (preg_match('/^[1-9A-Z]$/', $entityCount) !== 1) {
            $reasons[] = 'INVALID_ENTITY_COUNT';
        }

        if ($gstin[13] !== 'Z') {
            $reasons[] = 'INVALID_DEFAULT_Z_CHAR';
        }

        $expectedCheckDigit = self::computeCheckDigit(substr($gstin, 0, 14));
        if ($gstin[14] !== $expectedCheckDigit) {
            $reasons[] = 'INVALID_CHECK_DIGIT';
        }

        return [
            'valid' => $reasons === [],
            'normalized' => $gstin,
            'state_code' => $stateCode,
            'reason_codes' => $reasons,
        ];
    }

    public static function computeCheckDigit(string $first14): string
    {
        $chars = strtoupper(trim($first14));
        $sum = 0;

        for ($i = 0; $i < 14; $i++) {
            $codePoint = strpos(self::CHARSET, $chars[$i]);
            if ($codePoint === false) {
                return '?';
            }
            $factor = ($i % 2 === 0) ? 1 : 2;
            $product = $codePoint * $factor;
            $quotient = intdiv($product, 36);
            $remainder = $product % 36;
            $sum += $quotient + $remainder;
        }

        $checkCodePoint = (36 - ($sum % 36)) % 36;

        return self::CHARSET[$checkCodePoint];
    }
}

