<?php

namespace App\Platform\Identity\Federation;

use Illuminate\Contracts\Cache\Repository;
use Laravel\Passport\Client;
use Laravel\Passport\Token;

/**
 * D2 revocation-status contract (ADR-0020): resource servers verify
 * token signatures locally but hold revocation status under a HARD
 * freshness bound (federation.revocation_status_ttl_seconds, default
 * 30 s). Positive verdicts are cached for at most that bound; negative
 * verdicts are never cached (fail closed, live read). Every first-party
 * revocation site invalidates immediately, so in-process revocation
 * propagates in 0 s and cross-process revocation (another node, a direct
 * DB write, a replica) is bounded by the TTL.
 *
 * Failure posture (D4): the cache store is an accelerator, never the
 * authority — any store error falls back to the authoritative read; a
 * dead store can slow a decision down but never bypass one. With the
 * default database store the "MySQL fallback" is the primary path.
 */
final class RevocationStatusCache
{
    private const TOKEN_PREFIX = 'authz:tok:';

    private const CLIENT_PREFIX = 'authz:client:';

    /**
     * The store is injected (not taken from the facade) so failure
     * postures are testable per-component: a dead repository must degrade
     * this class to authoritative reads without taking unrelated
     * cache-backed middleware (throttling, sessions) down with it.
     */
    public function __construct(private readonly Repository $cache)
    {
    }

    /**
     * Authoritative-or-cached validity for an access token. Returns the
     * minimal verdict (owning client) or null for revoked/unknown —
     * callers still enforce audience, user liveness and signature.
     *
     * @return array{client_id: string}|null
     */
    public function validToken(string $jti): ?array
    {
        $key = self::TOKEN_PREFIX.$jti;

        try {
            $cached = $this->cache->get($key);
        } catch (\Throwable) {
            $cached = null; // store down -> authoritative read
        }

        if (is_array($cached) && isset($cached['client_id'])) {
            return ['client_id' => (string) $cached['client_id']];
        }

        $stored = Token::query()->whereKey($jti)->where('revoked', false)->first();

        if ($stored === null) {
            return null;
        }

        $verdict = ['client_id' => (string) $stored->getAttribute('client_id')];

        // Cache only up to the bound AND never past the token's own
        // expiry — the entry must not outlive either clock.
        $ttl = min($this->ttlSeconds(), $this->remainingSeconds($stored));

        if ($ttl > 0) {
            try {
                $this->cache->put($key, $verdict, $ttl);
            } catch (\Throwable) {
                // Cache write failure never fails the request.
            }
        }

        return $verdict;
    }

    /**
     * Client liveness (registered + not revoked) under the same bound —
     * client revocation must deny within it even for token entries that
     * were cached before the flip.
     */
    public function clientActive(string $clientId): bool
    {
        $key = self::CLIENT_PREFIX.$clientId;

        try {
            $cached = $this->cache->get($key);
        } catch (\Throwable) {
            $cached = null;
        }

        if (is_bool($cached)) {
            return $cached;
        }

        $active = Client::query()->whereKey($clientId)->where('revoked', false)->exists();

        try {
            $this->cache->put($key, $active, $this->ttlSeconds());
        } catch (\Throwable) {
        }

        return $active;
    }

    /**
     * Immediate invalidation (D2 "revocation events invalidate sooner"):
     * called by every first-party revocation site so the bound degrades
     * to zero staleness for revocations this node knows about.
     *
     * @param  list<string>|string  $jtis
     */
    public function invalidateTokens(array|string $jtis): void
    {
        foreach ((array) $jtis as $jti) {
            try {
                $this->cache->forget(self::TOKEN_PREFIX.$jti);
            } catch (\Throwable) {
            }
        }
    }

    public function invalidateClient(string $clientId): void
    {
        try {
            $this->cache->forget(self::CLIENT_PREFIX.$clientId);
        } catch (\Throwable) {
        }
    }

    private function ttlSeconds(): int
    {
        return max(0, (int) config('federation.revocation_status_ttl_seconds', 30));
    }

    private function remainingSeconds(Token $token): int
    {
        $expiresAt = $token->getAttribute('expires_at');

        if ($expiresAt === null) {
            return 0;
        }

        $epoch = $expiresAt instanceof \DateTimeInterface
            ? $expiresAt->getTimestamp()
            : (strtotime((string) $expiresAt) ?: 0);

        return max(0, $epoch - time());
    }
}
