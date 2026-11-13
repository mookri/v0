<?php

namespace App\Modules\Search\Support;

class PiiScrubber
{
    /**
     * Scrub PII (emails, phone numbers, identity numbers) and normalize query text.
     */
    public static function scrub(string $query): string
    {
        // 1. Remove control characters and null bytes
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $query) ?? '';

        // 2. Redact email addresses
        $clean = preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/u', '[EMAIL]', $clean) ?? '';

        // 3. Redact Indian mobile numbers / 10-digit phone numbers (+91-XXXXX or 10 digits)
        $clean = preg_replace('/(\+91[\-\s]?)?[6-9]\d{9}\b/u', '[PHONE]', $clean) ?? '';
        $clean = preg_replace('/\b\d{3}[-\s]\d{3}[-\s]\d{4}\b/u', '[PHONE]', $clean) ?? '';

        // 4. Redact 12-digit Aadhaar / 16-digit card patterns
        $clean = preg_replace('/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}([-\s]?\d{4})?\b/u', '[REDACTED_NUM]', $clean) ?? '';

        // 5. Redact 10-character PAN patterns (5 letters, 4 digits, 1 letter)
        $clean = preg_replace('/\b[A-Z]{5}[0-9]{4}[A-Z]\b/ui', '[PAN]', $clean) ?? '';

        // 6. Normalize whitespace
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');

        // 7. Cap length to 255 chars
        return mb_substr($clean, 0, 255);
    }
}
