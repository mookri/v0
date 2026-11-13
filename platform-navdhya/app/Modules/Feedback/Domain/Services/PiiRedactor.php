<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Domain\Services;

/**
 * Strips personal contact details / PII from public review copies while
 * preserving the submitter's original verbatim submission intact (TS-06 / ADR-0061).
 */
final class PiiRedactor
{
    /**
     * @return array{redacted: string, changed: bool, patterns: list<string>}
     */
    public static function redact(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return ['redacted' => (string) $text, 'changed' => false, 'patterns' => []];
        }

        $patterns = [];
        $output = $text;

        $emailPattern = '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/';
        if (preg_match($emailPattern, $output) === 1) {
            $output = (string) preg_replace($emailPattern, '[redacted-email]', $output);
            $patterns[] = 'email';
        }

        // Indian 10-digit mobile numbers or +91 prefixed variants
        $phonePattern = '/(?:\+91[\-\s]?)?[6-9]\d{9}\b/';
        if (preg_match($phonePattern, $output) === 1) {
            $output = (string) preg_replace($phonePattern, '[redacted-phone]', $output);
            $patterns[] = 'phone';
        }

        // 12-digit Aadhaar-shaped identifiers
        $aadhaarPattern = '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/';
        if (preg_match($aadhaarPattern, $output) === 1) {
            $output = (string) preg_replace($aadhaarPattern, '[redacted-id]', $output);
            $patterns[] = 'national_id';
        }

        return [
            'redacted' => $output,
            'changed' => $patterns !== [],
            'patterns' => $patterns,
        ];
    }
}

