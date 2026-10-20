<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\ReferralAttributionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Referral attribution row (tenant_shared, append-only).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $referrer_user_id
 * @property int $referred_user_id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ReferralAttribution extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<ReferralAttributionFactory> */
    use HasFactory;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'referrer_user_id', 'referred_user_id', 'code',
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

    /**
     * @return Factory<ReferralAttribution>
     */
    protected static function newFactory(): Factory
    {
        return ReferralAttributionFactory::new();
    }
}
