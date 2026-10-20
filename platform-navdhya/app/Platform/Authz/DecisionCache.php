<?php

namespace App\Platform\Authz;

use Illuminate\Contracts\Cache\Repository;
use Throwable;

/**
 * SEC-022 decision cache (T-0K01-02.2, AC-T-0K01-02-3): memoizes chain
 * decisions per (tenant, generation, principal kind, membership,
 * capability, evaluation context). The evaluation context covers the
 * bounded condition attributes plus the resource reference and Working
 * Context so any change in request shape gets its own entry.
 *
 * Invalidation is generation-based: AuthzCacheInvalidator bumps the
 * tenant generation on any policy / grant / registry mutation (platform
 * default rows bump the global generation), so a stale decision becomes
 * unreachable within the same request cycle in which authorization state
 * changed. The short TTL is the belt-and-braces bound required by the
 * task risk register.
 */
final class DecisionCache
{
    private const GLOBAL_GENERATION_KEY = 'authz:generation:platform';

    public function __construct(private readonly Repository $cache)
    {
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function get(string $tenantId, string $principalKind, string $membershipId, string $capability, array $context): ?AuthorizationDecision
    {
        $payload = $this->cache->get($this->key($tenantId, $principalKind, $membershipId, $capability, $context));

        if (! is_array($payload)) {
            return null;
        }

        try {
            return AuthorizationDecision::fromArray($payload)->withCached();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function put(string $tenantId, string $principalKind, string $membershipId, string $capability, array $context, AuthorizationDecision $decision): void
    {
        $this->cache->put(
            $this->key($tenantId, $principalKind, $membershipId, $capability, $context),
            $decision->toArray(),
            $this->ttl(),
        );
    }

    public function invalidate(string $tenantId): void
    {
        $key = $this->generationKey($tenantId);

        $this->cache->forever($key, ((int) $this->cache->get($key, 1)) + 1);
    }

    public function invalidateAll(): void
    {
        $this->cache->forever(
            self::GLOBAL_GENERATION_KEY,
            ((int) $this->cache->get(self::GLOBAL_GENERATION_KEY, 1)) + 1,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function key(string $tenantId, string $principalKind, string $membershipId, string $capability, array $context): string
    {
        $normalized = $context;
        $this->ksortRecursive($normalized);

        return implode(':', [
            'authz',
            'decision',
            $tenantId,
            (string) $this->generation($tenantId),
            $principalKind,
            $membershipId,
            $capability,
            hash('sha256', (string) json_encode($normalized, JSON_UNESCAPED_SLASHES)),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $array
     */
    private function ksortRecursive(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    private function generation(string $tenantId): int
    {
        return (int) $this->cache->get($this->generationKey($tenantId), 1)
            + (int) $this->cache->get(self::GLOBAL_GENERATION_KEY, 1);
    }

    private function generationKey(string $tenantId): string
    {
        return 'authz:generation:'.$tenantId;
    }

    private function ttl(): int
    {
        return (int) config('platform.authz.decision_cache.ttl', 60);
    }
}
