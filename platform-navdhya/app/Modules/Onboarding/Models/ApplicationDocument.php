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
 */
final class ApplicationDocument extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_VERIFIED = 'VERIFIED';

    public const STATUS_REJECTED = 'REJECTED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'application_id', 'kind', 'status',
        'object_key', 'sha256', 'reason_code',
    ];

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
