<?php

namespace App\Platform\Identity;

/**
 * HMAC actor-token issuance (token bridge, first half): mints the opaque
 * bearer tokens ActorTokenVerifier accepts — base64url(payload).
 * base64url(HMAC-SHA256) with claims {v,u,s,r[,m,o],e}. Backend-issued
 * tokens (customer login today) verify under the same secret as the
 * frontend-minted ones, so AuthenticateAny resolves both identically.
 * No refresh semantics: absolute TTL only (Passport rotation later).
 *
 * Since M5/TOKEN-001 every mint also persists its jti in actor_sessions —
 * the revocation store ActorTokenVerifier fails closed against.
 */
final class ActorTokenIssuer
{
    /** Absolute TTL for backend-issued customer sessions. */
    public const TOKEN_TTL_SECONDS = 86400;

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

        // M5 / TOKEN-001: persist the jti so logout, rotation and
        // suspension can revoke this token before its absolute expiry.
        Models\ActorSession::query()->create([
            'id' => $sessionId,
            'user_id' => $userId,
            'issued_at' => gmdate('Y-m-d H:i:s', $now),
            'expires_at' => gmdate('Y-m-d H:i:s', $now + $ttlSeconds),
        ]);

        return [
            'token' => $payload.'.'.$signature,
            'session_id' => $sessionId,
            'expires_at' => $expiresAt,
        ];
    }
}
