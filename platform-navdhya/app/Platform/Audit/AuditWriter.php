<?php

namespace App\Platform\Audit;

use App\Platform\Tenancy\Exceptions\TenantContextMissingException;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Kernel audit writer (K-025, CON-004): every state change and every
 * authorization denial writes a tenant-scoped audit row with actor,
 * action, resource reference (K-012), before/after payloads, request id
 * and a per-tenant hash chain.
 *
 * Fail-closed: writing outside a resolved TenantContext throws — audit
 * rows never leak across tenants (TEN-009).
 */
final class AuditWriter
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    /**
     * @param  array{module?: string, resource_type?: string, resource_id?: string}|null  $resource
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(string $action, ?array $resource = null, ?array $before = null, ?array $after = null): AuditLog
    {
        $tenantId = $this->context->tenantId();

        if (! $this->context->isResolved() || $tenantId === null) {
            throw new TenantContextMissingException('writing audit record');
        }

        $actor = $this->actor();
        $requestId = $this->requestId();
        $occurredAt = now()->utc()->startOfSecond();

        // Hash chain: each row seals its predecessor (tamper evidence).
        // Bypass the tenant scope deliberately — the row set is already
        // restricted to this tenant by the explicit where clause below
        // (architecture-tested bypass site, TEN-009).
        $connection = (new AuditLog())->getConnectionName() ?: (string) config('database.default');

        return DB::connection($connection)->transaction(function () use ($tenantId, $actor, $requestId, $occurredAt, $action, $resource, $before, $after): AuditLog {
            $prevHash = AuditLog::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('hash');

            $hash = self::computeHash([
                'tenant_id' => $tenantId,
                'actor' => $actor,
                'action' => $action,
                'resource' => $resource,
                'before' => $before,
                'after' => $after,
                'request_id' => $requestId,
                'prev_hash' => $prevHash,
                'occurred_at' => $occurredAt->toISOString(),
            ]);

            return AuditLog::create([
                'tenant_id' => $tenantId,
                'actor' => $actor,
                'action' => $action,
                'resource' => $resource,
                'before' => $before,
                'after' => $after,
                'request_id' => $requestId,
                'prev_hash' => $prevHash,
                'hash' => $hash,
                'occurred_at' => $occurredAt,
            ]);
        });
    }

    /**
     * Canonical seal digest shared between writer and chain verifier.
     *
     * @param  array<string, mixed>  $seal
     */
    public static function computeHash(array $seal): string
    {
        return hash('sha256', (string) json_encode([
            'tenant_id' => $seal['tenant_id'] ?? null,
            'actor' => $seal['actor'] ?? null,
            'action' => $seal['action'] ?? null,
            'resource' => $seal['resource'] ?? null,
            'before' => $seal['before'] ?? null,
            'after' => $seal['after'] ?? null,
            'request_id' => $seal['request_id'] ?? null,
            'prev_hash' => $seal['prev_hash'] ?? null,
            'occurred_at' => $seal['occurred_at'] ?? null,
        ], JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array{principal_id: string, kind: string, user_id?: string}|null
     */
    private function actor(): ?array
    {
        $user = Auth::user();

        if (! $user instanceof Authenticatable) {
            return null;
        }

        $userId = (string) $user->getAuthIdentifier();

        return [
            'principal_id' => 'user:'.$userId,
            'kind' => 'user',
            'user_id' => $userId,
        ];
    }

    private function requestId(): ?string
    {
        $request = app('request');

        if (! $request instanceof Request) {
            return null;
        }

        $requestId = $request->attributes->get('request_id');

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
