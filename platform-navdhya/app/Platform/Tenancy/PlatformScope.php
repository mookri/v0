<?php

namespace App\Platform\Tenancy;

use App\Platform\Tenancy\Models\Tenant;
use Closure;
use InvalidArgumentException;

/**
 * Explicit, audited platform scope runner for system jobs, public reads, and
 * scoped internal operations (T-AP03-01, ADR-0069).
 *
 * Replaces raw `->withoutGlobalScopes()` calls so that every deliberate
 * cross-tenant or system context switch requires a human-readable `$reason`,
 * binds or bypasses `TenantScope` safely, and restores the prior ambient
 * `TenantContext` inside a `finally` block (including on uncaught exceptions).
 */
final class PlatformScope
{
    /** @var list<array{reason: string, bypass: bool, tenant_id: ?string}> */
    private static array $stack = [];

    /**
     * Run `$callback` within an explicit platform scope.
     *
     * - When `$tenantId` is non-null, temporarily binds `TenantContext` to
     *   that tenant (keeping `TenantScope` strictly active for `$tenantId`).
     * - When `$tenantId` is null, opens an audited cross-tenant system read
     *   bypass for `TenantScope` and restores prior context on exit.
     *
     * @template TReturn
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function run(string $reason, Closure $callback, ?string $tenantId = null): mixed
    {
        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new InvalidArgumentException('PlatformScope::run requires a non-empty reason.');
        }

        $context = app(TenantContext::class);
        $previous = [
            'resolved' => $context->isResolved(),
            'tenant_id' => $context->tenantId(),
            'membership_id' => $context->membershipId(),
            'org_id' => $context->orgId(),
            'connection_name' => $context->connectionName(),
            'actor' => $context->actor(),
        ];

        $isBypass = $tenantId === null;
        self::$stack[] = [
            'reason' => $trimmedReason,
            'bypass' => $isBypass,
            'tenant_id' => $tenantId,
        ];

        try {
            if ($tenantId !== null) {
                $sameTenant = $previous['resolved'] && $previous['tenant_id'] === $tenantId;
                $connectionName = $sameTenant
                    ? $previous['connection_name']
                    : self::resolveConnectionName($tenantId);

                $context->clear();
                $context->set(
                    tenantId: $tenantId,
                    membershipId: $sameTenant ? $previous['membership_id'] : null,
                    orgId: $sameTenant ? $previous['org_id'] : null,
                    connectionName: $connectionName,
                    actor: $sameTenant ? $previous['actor'] : null,
                );
            }

            return $callback();
        } finally {
            array_pop(self::$stack);
            $context->clear();
            if ($previous['resolved']) {
                $context->set(
                    tenantId: $previous['tenant_id'],
                    membershipId: $previous['membership_id'],
                    orgId: $previous['org_id'],
                    connectionName: $previous['connection_name'],
                    actor: $previous['actor'],
                );
            }
        }
    }

    /**
     * Whether the innermost active `PlatformScope::run()` frame is an
     * intentional cross-tenant system bypass (`$tenantId === null`).
     */
    public static function isSystemBypassActive(): bool
    {
        if (self::$stack === []) {
            return false;
        }

        $top = self::$stack[array_key_last(self::$stack)];

        return $top['bypass'];
    }

    public static function currentReason(): ?string
    {
        if (self::$stack === []) {
            return null;
        }

        return self::$stack[array_key_last(self::$stack)]['reason'];
    }

    private static function resolveConnectionName(string $tenantId): string
    {
        try {
            $tenant = Tenant::query()->find($tenantId);
            if ($tenant !== null) {
                return app(ConnectionResolver::class)->cachedFor($tenant);
            }
        } catch (\Throwable) {
            // Fall back to shared connection in bootstrap/minimal test contexts.
        }

        return ConnectionResolver::SHARED_CONNECTION;
    }
}

