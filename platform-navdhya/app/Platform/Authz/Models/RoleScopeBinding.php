<?php

namespace App\Platform\Authz\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\RoleScopeBindingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SEC-021 role-assignment scoping: binds a role assignment to a scope
 * (scope_type/scope_id resolved against hierarchy nodes; the hierarchy
 * wiring lands in T-0K02-01) with an optional HIER-005 scope mode.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $role_assignment_id
 * @property string $scope_type
 * @property string $scope_id
 * @property string|null $scope_mode
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class RoleScopeBinding extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<RoleScopeBindingFactory> */
    use HasFactory;
    use HasUlids;

    protected $connection = 'central';

    protected $fillable = ['role_assignment_id', 'scope_type', 'scope_id', 'scope_mode'];

    /**
     * @return Factory<RoleScopeBinding>
     */
    protected static function newFactory(): Factory
    {
        return RoleScopeBindingFactory::new();
    }

    /**
     * @return BelongsTo<MembershipRoleAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(MembershipRoleAssignment::class, 'role_assignment_id');
    }
}
