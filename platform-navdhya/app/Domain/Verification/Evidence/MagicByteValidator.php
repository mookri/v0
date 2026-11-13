<?php

namespace App\Domain\Verification\Evidence;

/**
 * Validates binary magic bytes rather than trusting declared Content-Type or filename extension.
 * Rejects polyglots, disguised archives (ZIP/RAR/EXE), and active-script PDFs (SPEC §5.4 / T-TR01-03).
 */
final class MagicByteValidator
{
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    /**
     * @return array{valid: bool, detected_mime: string|null, reason_code: string|null}
     */
    public function inspect(string $filePath, ?string $declaredMime = null): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            return ['valid' => false, 'detected_mime' => null, 'reason_code' => 'unreadable_upload'];
        }

        $bytes = (string) file_get_contents($filePath, false, null, 0, 65536);
        if ($bytes === '') {
            return ['valid' => false, 'detected_mime' => null, 'reason_code' => 'empty_upload'];
        }

        $detected = $this->detectMagicMime($bytes);
        if ($detected === null) {
            return ['valid' => false, 'detected_mime' => null, 'reason_code' => 'unsupported_magic_bytes'];
        }

        if ($declaredMime !== null && $declaredMime !== '' && $declaredMime !== $detected) {
            return ['valid' => false, 'detected_mime' => $detected, 'reason_code' => 'mime_mismatch'];
        }

        // Reject polyglot / disguised ZIP containers embedded inside images or PDFs
        if (str_contains($bytes, "PK\x03\x04") || preg_match('/<\s*(?:script|html|svg)/i', $bytes) === 1) {
            return ['valid' => false, 'detected_mime' => $detected, 'reason_code' => 'polyglot_payload_rejected'];
        }

        // Reject PDFs embedding active JavaScript or auto-launch actions
        if ($detected === 'application/pdf') {
            $full = (string) file_get_contents($filePath);
            if (preg_match('/\/(?:JavaScript|JS|Launch|OpenAction|RichMedia)\b/i', $full) === 1) {
                return ['valid' => false, 'detected_mime' => $detected, 'reason_code' => 'active_pdf_script_rejected'];
            }
        }

        return ['valid' => true, 'detected_mime' => $detected, 'reason_code' => null];
    }

    private function detectMagicMime(string $bytes): ?string
    {
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (str_starts_with($bytes, '%PDF-')) {
            return 'application/pdf';
        }

        return null;
    }
}
