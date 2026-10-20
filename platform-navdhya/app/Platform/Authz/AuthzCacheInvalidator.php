<?php

namespace App\Platform\Authz;

/**
 * Decision-cache invalidation hooks (T-0K01-02.2, AC-T-0K01-02-3): every
 * mutation of authorization state — policy lifecycle changes, capability
 * registry syncs, grant changes — bumps the owning tenant's decision
 * generation so cached decisions become unreachable within the same
 * request cycle in which the state changed. Platform-default rows
 * (tenant_id NULL) affect every tenant and bump the global generation.
 */
final class AuthzCacheInvalidator
{
    public function __construct(private readonly DecisionCache $cache)
    {
    }

    public function invalidateForPolicy(?string $tenantId): void
    {
        if ($tenantId === null) {
            $this->cache->invalidateAll();

            return;
        }

        $this->cache->invalidate($tenantId);
    }

    public function invalidateTenant(string $tenantId): void
    {
        $this->cache->invalidate($tenantId);
    }
}
