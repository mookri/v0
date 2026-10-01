<?php

namespace App\Platform\Tenancy\Http\Middleware;

use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Exceptions\MembershipInactiveException;
use App\Platform\Tenancy\Exceptions\TenantHeaderMissingException;
use App\Platform\Tenancy\Exceptions\TenantNotInTokenException;
use App\Platform\Tenancy\Exceptions\TenantSuspendedException;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant resolution for API surfaces (doc 02 §4, TEN-006): resolves the
 * ambient TenantContext from the X-Tenant-Id header, validated against the
 * authenticated principal's memberships. Fail-closed: a missing header,
 * unknown or suspended tenant, or missing/inactive membership each deny
 * with a stable envelope code (tenant_header_missing / tenant_not_in_token
 * / tenant_suspended / membership_inactive).
 *
 * Typed against the Authenticatable contract (never a concrete app model)
 * so the kernel layer keeps its dependency boundary (doc 01 §7).
 */
final class ResolveTenantContext
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly ConnectionResolver $connections,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = trim((string) $request->header('X-Tenant-Id', ''));

        if ($tenantId === '') {
            throw new TenantHeaderMissingException();
        }

        $tenant = Tenant::find($tenantId);

        if ($tenant === null) {
            throw new TenantNotInTokenException();
        }

        if ($tenant->state !== Tenant::STATE_ACTIVE) {
            throw new TenantSuspendedException();
        }

        $user = $request->user();

        if (! $user instanceof Authenticatable) {
            // The auth middleware should have denied already; fail closed.
            throw new TenantNotInTokenException();
        }

        $membership = TenantMembership::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($membership === null) {
            throw new MembershipInactiveException('missing');
        }

        if ($membership->state !== TenantMembership::STATE_ACTIVE) {
            throw new MembershipInactiveException((string) $membership->state);
        }

        $this->context->set(
            (string) $tenant->getKey(),
            (string) $membership->getKey(),
            $membership->org_node_id !== null ? (string) $membership->org_node_id : null,
            $this->connections->cachedFor($tenant),
        );

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->context->clear();
    }
}
