<?php

namespace App\Modules\Onboarding\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Application document metadata (bytes in object storage; this row is the
 * tenant-scoped record). Verification transitions PENDING→VERIFIED/
 * REJECTED with machine-readable reason codes.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $application_id
 * @property string $kind
 * @property string $status
 * @property string $object_key
 * @property string|null $sha256
 * @property string|null $reason_code
 * @property string $integrity_state
 * @property \Illuminate\Support\Carbon|null $integrity_last_checked_at
 * @property string $quarantine_status
 */
final class ApplicationDocument extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_VERIFIED = 'VERIFIED';

    public const STATUS_REJECTED = 'REJECTED';

    public const INTEGRITY_UNVERIFIED = 'unverified';
    public const INTEGRITY_VERIFIED = 'verified';
    public const INTEGRITY_CORRUPTED = 'corrupted';
    public const INTEGRITY_MISSING = 'missing';

    public const QUARANTINE_PENDING = 'pending_scan';
    public const QUARANTINE_CLEAN = 'clean';
    public const QUARANTINE_QUARANTINED = 'quarantined';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'application_id', 'kind', 'status',
        'object_key', 'sha256', 'reason_code',
        'integrity_state', 'integrity_last_checked_at', 'quarantine_status',
        'rejection_reason_code', 'reviewed_by', 'reviewed_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'integrity_last_checked_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getConnectionName(): ?string
    {
        try {
            $context = app(\App\Platform\Tenancy\TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }
}
