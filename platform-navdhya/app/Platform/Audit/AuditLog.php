<?php

namespace App\Platform\Audit;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit log row (K-025) on the tenant data connection (TEN-003 §2.2):
 * audit rows MUST commit in the same database transaction as the domain
 * change they record, so they follow the tenant's db_mode connection.
 * Rows form a hash chain per tenant (prev_hash/hash) so tampering is
 * detectable. The table carries occurred_at instead of Laravel
 * timestamps; tenant_id is mass-assignable because the kernel audit
 * writer always stamps it explicitly from the resolved context.
 *
 * @property string $id
 * @property string $tenant_id
 * @property array<string, mixed>|null $actor
 * @property string $action
 * @property array<string, mixed>|null $resource
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property string|null $request_id
 * @property string|null $prev_hash
 * @property string $hash
 * @property \Illuminate\Support\Carbon $occurred_at
 */
final class AuditLog extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $connection = 'tenant_shared';

    /**
     * Tenant data models resolve their connection from the ambient context
     * (TEN-012): the resolver routes dedicated tenants to their own
     * connection. Unresolved contexts fall back to the shared connection;
     * writes still fail closed via BelongsToTenant.
     */
    public function getConnectionName(): ?string
    {
        try {
            $context = app(\App\Platform\Tenancy\TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }

    protected $fillable = [
        'tenant_id', 'actor', 'action', 'resource', 'before', 'after',
        'request_id', 'prev_hash', 'hash', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'actor' => 'array',
            'resource' => 'array',
            'before' => 'array',
            'after' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
