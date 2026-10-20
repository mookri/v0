<?php

namespace App\Platform\Authz;

use App\Platform\Authz\Exceptions\AuthorizationDeniedException;
use App\Platform\Authz\Models\Role;
use App\Platform\Authz\Models\RolePermission;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Kernel capability authorizer — the Phase-0K floor of the SEC-022 chain.
 * Resolves: authenticated principal -> active membership (via the resolved
 * TenantContext) -> active-role grants containing the capability code.
 *
 * Fail-closed at every step (CON-003): an unresolved tenant context,
 * missing membership id, or absent grant denies. The full precedence
 * chain (explicit DENY, security constraints, policy conditions,
 * delegation) lands in T-0K01-02; this class is the integration point it
 * extends.
 */
final class CapabilityAuthorizer
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function allows(?Authenticatable $user, string $capability): bool
    {
        if ($user === null || ! $this->context->isResolved()) {
            return false;
        }

        $membershipId = $this->context->membershipId();
        $tenantId = $this->context->tenantId();

        if ($membershipId === null || $tenantId === null) {
            return false;
        }

        return RolePermission::query()
            ->where('capability_code', $capability)
            ->whereHas('role', function (Builder $role) use ($membershipId): void {
                /** @var Builder<Role> $role */
                $role->where('state', Role::STATE_ACTIVE)
                    ->whereHas('assignments', function (Builder $assignment) use ($membershipId): void {
                        $assignment->where('membership_id', $membershipId);
                    });
            })
            ->exists();
    }

    /**
     * @throws AuthorizationDeniedException
     */
    public function ensure(?Authenticatable $user, string $capability): void
    {
        if ($this->allows($user, $capability)) {
            return;
        }

        throw new AuthorizationDeniedException(
            capability: $capability,
            reason: $user === null ? 'unauthenticated' : 'capability_not_granted',
        );
    }
}
