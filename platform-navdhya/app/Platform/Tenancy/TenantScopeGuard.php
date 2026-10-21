<?php

namespace App\Platform\Tenancy;

use App\Platform\Tenancy\Exceptions\TenantNotInTokenException;
use Illuminate\Http\Request;

/**
 * Tenant scope guard (TEN-006 fail-closed).
 *
 * A route resolves the tenant from the `X-Tenant-Id` header through
 * `ResolveTenantContext`, which also proves membership and checks the
 * capability IN THAT TENANT. A controller that then reads a second
 * tenant id from the request body or query string must prove the two
 * agree, otherwise the capability check and the data read refer to
 * different tenants.
 *
 * This was not done: the platform ops surfaces took `?tenant_id=` and
 * used it directly, so a principal holding `platform:ops:view` in
 * tenant A could read tenant B's member counts and settings by
 * pointing the query parameter at B.
 */
final class TenantScopeGuard
{
    /**
     * Assert the requested tenant is the resolved one.
     *
     * @throws TenantNotInTokenException
     */
    public static function assertSameTenant(Request $request, string $tenantId): void
    {
        $context = app(TenantContext::class);

        if (! $context->isResolved()) {
            throw new TenantNotInTokenException();
        }

        if ((string) $context->tenantId() !== $tenantId) {
            throw new TenantNotInTokenException();
        }
    }
}
