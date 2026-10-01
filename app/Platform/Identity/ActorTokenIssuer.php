<?php

namespace App\Platform\Identity;

/**
 * HMAC actor-token issuance (token bridge, first half): mints the opaque
 * bearer tokens ActorTokenVerifier accepts — base64url(payload).
 * base64url(HMAC-SHA256) with claims {v,u,s,r[,m,o],e}. Backend-issued
 * tokens (customer login today) verify under the same secret as the
 * frontend-minted ones, so AuthenticateAny resolves both identically.
 * No refresh semantics: absolute TTL only (Passport rotation later).
 */
final class ActorTokenIssuer
{
    /**
     * @param list<string> $roles
     * @return array{token: string, session_id: string, expires_at: string}
     */
    public static function mint(
        string $secret,
        string $userId,
        int $ttlSeconds,
        string $nowIso,
        array $roles = [],
        ?string $market = null,
        ?string $orgId = null,
    ): array {
        $now = strtotime($nowIso) ?: time();
        $expiresAt = gmdate('Y-m-d\TH:i:s.v\Z', $now + $ttlSeconds);
        $sessionId = 'sess_'.strtolower((string) \Illuminate\Support\Str::ulid());

        $payload = ActorTokenVerifier::base64UrlEncode((string) json_encode([
            'v' => 1,
            'u' => $userId,
            's' => $sessionId,
            'r' => implode(',', $roles),
            'm' => $market,
            'o' => $orgId,
            'e' => $expiresAt,
        ]));
        $signature = ActorTokenVerifier::base64UrlEncode(
            hash_hmac('sha256', $payload, $secret, true)
        );

        return [
            'token' => $payload.'.'.$signature,
            'session_id' => $sessionId,
            'expires_at' => $expiresAt,
        ];
    }
}
