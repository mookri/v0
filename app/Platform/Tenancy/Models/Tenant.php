<?php

namespace App\Platform\Tenancy\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tenant registry record (central connection, TEN-005). Central tables
 * carry tenant_id as data, not as a row-level scope: isolation of tenant
 * business data is enforced by the resolution middleware (TEN-006) and the
 * BelongsToTenant scope on tenant tables (TEN-009).
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $db_mode
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TenantMembership> $memberships
 */
final class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;
    use HasUlids;

    public const STATE_ACTIVE = 'active';

    public const STATE_SUSPENDED = 'suspended';

    public const STATE_PROMOTING = 'promoting';

    public const STATE_DEPROVISIONED = 'deprovisioned';

    protected $connection = 'central';

    protected $fillable = ['code', 'slug', 'name', 'db_mode', 'state', 'status', 'plan_code', 'base_currency', 'timezone', 'locale'];

    /**
     * @return Factory<Tenant>
     */
    protected static function newFactory(): Factory
    {
        return TenantFactory::new();
    }

    /**
     * @return HasMany<TenantMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }
}
