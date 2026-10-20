<?php

namespace App\Platform\Identity;

/**
 * HMAC actor-token verification (frontend token bridge, first half).
 *
 * Verifies the opaque bearer tokens minted by the Next.js BFFs:
 * base64url(payload).base64url(HMAC-SHA256) over the payload segment,
 * claims {v,u,s,r[,m,o],e}. Timing-safe compare, expiry enforced,
 * field hygiene (no dots/commas) matching the minter, and — since
 * M5/TOKEN-001 — a live jti lookup against actor_sessions: missing or
 * revoked sessions fail closed before the claims ever surface.
 *
 * Second half (Passport JWT + dual-accept window) lands with the token
 * program; this verifier lets Laravel authoritative-check frontend
 * sessions today with zero new dependencies.
 */
final class ActorTokenVerifier
{
    /**
     * @return array{user_id: string, session_id: string, roles: list<string>, market: ?string, org_id: ?string}|null
     */
    public static function verify(string $secret, string $token, string $nowIso): ?array
    {
        if ($secret === '' || $token === '') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }
        [$payload, $signature] = $parts;

        $expected = self::base64UrlEncode(hash_hmac('sha256', $payload, $secret, true));
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if (! is_string($decoded)) {
            return null;
        }
        $claims = json_decode($decoded, true);
        if (! is_array($claims)) {
            return null;
        }

        if (($claims['v'] ?? null) !== 1) {
            return null;
        }
        $userId = $claims['u'] ?? null;
        $sessionId = $claims['s'] ?? null;
        $expiresAt = $claims['e'] ?? null;
        if (! is_string($userId) || $userId === '' || ! is_string($sessionId) || $sessionId === '' || ! is_string($expiresAt)) {
            return null;
        }
        if ($expiresAt <= $nowIso) {
            return null;
        }
        foreach ([$userId, $sessionId] as $field) {
            if (str_contains($field, '.') || str_contains($field, ',')) {
                return null;
            }
        }

        // M5 / TOKEN-001 revocation: the session id is a persisted jti —
        // a missing (swept or never-issued) or revoked row fails closed
        // here, after the timing-safe signature compare.
        $session = Models\ActorSession::query()->find($sessionId);
        if ($session === null || $session->revoked_at !== null) {
            return null;
        }

        $roles = [];
        if (isset($claims['r']) && is_string($claims['r']) && $claims['r'] !== '') {
            $roles = explode(',', $claims['r']);
        }

        $market = isset($claims['m']) && is_string($claims['m']) && $claims['m'] !== '' ? $claims['m'] : null;
        $orgId = isset($claims['o']) && is_string($claims['o']) && $claims['o'] !== '' ? $claims['o'] : null;

        return [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'roles' => $roles,
            'market' => $market,
            'org_id' => $orgId,
        ];
    }

    public static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
