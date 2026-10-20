<?php

namespace App\Platform\Authz\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\MembershipRoleAssignmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Membership -> role assignment (central authorization group, TEN-003).
 * Assignments may carry scope bindings (SEC-021 role-assignment scoping).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $membership_id
 * @property string $role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class MembershipRoleAssignment extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<MembershipRoleAssignmentFactory> */
    use HasFactory;
    use HasUlids;

    protected $connection = 'central';

    protected $fillable = ['membership_id', 'role_id'];

    /**
     * @return Factory<MembershipRoleAssignment>
     */
    protected static function newFactory(): Factory
    {
        return MembershipRoleAssignmentFactory::new();
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return HasMany<RoleScopeBinding, $this>
     */
    public function scopeBindings(): HasMany
    {
        return $this->hasMany(RoleScopeBinding::class, 'role_assignment_id');
    }
}
