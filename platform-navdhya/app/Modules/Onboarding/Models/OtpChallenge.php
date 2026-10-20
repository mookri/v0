<?php

namespace App\Modules\Onboarding\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * OTP challenge row: hashed code, expiry, attempt accounting. Codes never
 * persist in plaintext; verification consumes the row.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $identifier
 * @property string $code_hash
 * @property \Illuminate\Support\Carbon $expires_at
 * @property int $attempts
 * @property int $max_attempts
 */
final class OtpChallenge extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'identifier', 'code_hash', 'expires_at',
        'attempts', 'max_attempts',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
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
