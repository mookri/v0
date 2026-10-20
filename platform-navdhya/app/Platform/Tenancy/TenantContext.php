<?php

namespace App\Platform\Tenancy;

use App\Platform\Tenancy\Exceptions\TenantContextMissingException;

/**
 * TenantContext — the kernel's ambient tenancy state (doc 02 §4, TEN-007).
 *
 * Holds the resolved tenant id, active membership, org, connection name and
 * actor for the current request/job. Fail-closed: any code reading the
 * context outside a resolved scope MUST receive null and deny, never
 * default to "all tenants". Set exactly once per scope: a second set()
 * without an intervening clear() throws (programming error, never silent
 * overwrite).
 */
final class TenantContext
{
    private ?string $tenantId = null;

    private ?string $membershipId = null;

    private ?string $orgId = null;

    private ?string $connectionName = null;

    /** @var array<string, mixed>|null */
    private ?array $actor = null;

    private bool $resolved = false;

    /**
     * @param array<string, mixed>|null $actor
     */
    public function set(
        ?string $tenantId,
        ?string $membershipId = null,
        ?string $orgId = null,
        ?string $connectionName = null,
        ?array $actor = null,
    ): void {
        if ($this->resolved) {
            throw new TenantContextMissingException('context already resolved for this scope');
        }

        $this->tenantId = $tenantId;
        $this->membershipId = $membershipId;
        $this->orgId = $orgId;
        $this->connectionName = $connectionName;
        $this->actor = $actor;
        $this->resolved = $tenantId !== null;
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->membershipId = null;
        $this->orgId = null;
        $this->connectionName = null;
        $this->actor = null;
        $this->resolved = false;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Active tenant id; null outside a resolved scope (fail-closed callers
     * treat null as "deny").
     */
    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function membershipId(): ?string
    {
        return $this->membershipId;
    }

    public function orgId(): ?string
    {
        return $this->orgId;
    }

    public function connectionName(): ?string
    {
        return $this->connectionName;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function actor(): ?array
    {
        return $this->actor;
    }
}
