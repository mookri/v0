<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\BuyerProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Buyer profile (customers module identity anchor, ADR-0005): exactly one
 * row per (tenant, user), materialized at signup or invite acceptance.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 * Soft deletes enabled for account deletion (audit retained).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string|null $display_name
 * @property string $locale
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class BuyerProfile extends Model
{
    use BelongsToTenant;
    use HasUlids;
    use SoftDeletes;
    /** @use HasFactory<BuyerProfileFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DEACTIVATED = 'deactivated';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'user_id', 'display_name', 'locale', 'status',
    ];

    protected $dates = ['deleted_at'];

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
     * Scope: only non-deleted profiles (default).
     */
    public function scopeWithoutTrashed($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope: only deleted profiles.
     */
    public function scopeOnlyTrashed($query)
    {
        return $query->whereNotNull('deleted_at');
    }

    /**
     * Scope: include deleted profiles.
     */
    public function scopeWithTrashed($query)
    {
        return $query;
    }

    /**
     * @return Factory<BuyerProfile>
     */
    protected static function newFactory(): Factory
    {
        return BuyerProfileFactory::new();
    }
}
