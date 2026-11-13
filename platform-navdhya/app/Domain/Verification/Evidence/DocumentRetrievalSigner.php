<?php

namespace App\Domain\Verification\Evidence;

/**
 * Generates and verifies short-lived HMAC-signed retrieval tokens bound to
 * (tenant_id, document_id, viewer_id, expires_at <= 300s) (SPEC §5 / T-TR01-01).
 */
final class DocumentRetrievalSigner
{
    public const MAX_TTL_SECONDS = 300;

    public function sign(
        string $tenantId,
        string $documentId,
        string $viewerId,
        int $expiresAt,
        string $signingKey
    ): string {
        $payload = "{$tenantId}:{$documentId}:{$viewerId}:{$expiresAt}";

        return hash_hmac('sha256', $payload, $signingKey);
    }

    public function verify(
        string $tenantId,
        string $documentId,
        string $viewerId,
        int $expiresAt,
        string $signature,
        string $signingKey,
        ?int $now = null
    ): bool {
        $current = $now ?? time();
        if ($expiresAt < $current || $expiresAt > ($current + self::MAX_TTL_SECONDS + 5)) {
            return false;
        }

        if ($signature === '') {
            return false;
        }

        $expected = $this->sign($tenantId, $documentId, $viewerId, $expiresAt, $signingKey);

        return hash_equals($expected, $signature);
    }
}
