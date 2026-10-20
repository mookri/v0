<?php

namespace App\Platform\Tenancy\Models;

use App\Models\User;
use Database\Factories\TenantMembershipFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User<->tenant membership (central, K-002). State gates tenant entry
 * (TEN-006 fail-closed): only active memberships resolve a TenantContext.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string|null $org_node_id
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TenantMembership extends Model
{
    /** @use HasFactory<TenantMembershipFactory> */
    use HasFactory;
    use HasUlids;

    public const STATE_ACTIVE = 'active';

    public const STATE_SUSPENDED = 'suspended';

    public const STATE_EXPIRED = 'expired';

    protected $connection = 'central';

    protected $fillable = ['tenant_id', 'user_id', 'org_node_id', 'state'];

    /**
     * @return Factory<TenantMembership>
     */
    protected static function newFactory(): Factory
    {
        return TenantMembershipFactory::new();
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
