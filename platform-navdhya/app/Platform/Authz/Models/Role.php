<?php

namespace App\Platform\Authz\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role profile (central authorization group, TEN-003). Flat roles with
 * explicit capability grants — no inheritance (SEC register: role
 * hierarchy intentionally omitted).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Role extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<RoleFactory> */
    use HasFactory;
    use HasUlids;

    public const STATE_ACTIVE = 'active';

    public const STATE_DISABLED = 'disabled';

    protected $connection = 'central';

    protected $fillable = ['code', 'name', 'state'];

    /**
     * @return Factory<Role>
     */
    protected static function newFactory(): Factory
    {
        return RoleFactory::new();
    }

    /**
     * @return HasMany<RolePermission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * @return HasMany<MembershipRoleAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(MembershipRoleAssignment::class);
    }
}
