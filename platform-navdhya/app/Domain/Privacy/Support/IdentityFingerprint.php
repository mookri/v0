<?php

namespace App\Domain\Privacy\Support;

/**
 * Keyed HMAC-SHA256 identity fingerprint for privacy-preserving duplicate detection.
 * Raw PII / unsalted hashes are never written into ledgers or logs.
 */
final class IdentityFingerprint
{
    public static function hash(string $namespace, string $rawValue): string
    {
        $key = (string) config('privacy.fingerprint_key', config('app.key', 'navdhya-privacy-dev-key'));
        $normalized = strtolower(trim($namespace)) . ':' . preg_replace('/\s+/', '', strtoupper(trim($rawValue)));

        return hash_hmac('sha256', $normalized, $key);
    }
}

