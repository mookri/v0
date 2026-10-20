<?php

namespace App\Platform\Authz\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\RolePermissionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Role -> capability grant row (central authorization group, TEN-003).
 * capability_code references the capability_metadata registry.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $role_id
 * @property string $capability_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class RolePermission extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<RolePermissionFactory> */
    use HasFactory;
    use HasUlids;

    protected $connection = 'central';

    protected $fillable = ['role_id', 'capability_code'];

    /**
     * @return Factory<RolePermission>
     */
    protected static function newFactory(): Factory
    {
        return RolePermissionFactory::new();
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
