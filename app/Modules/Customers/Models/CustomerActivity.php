<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\CustomerActivityFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer activity row (tenant_shared): kind + pre-rendered label.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $kind
 * @property string $label
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class CustomerActivity extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<CustomerActivityFactory> */
    use HasFactory;

    /** @var list<string> */
    public const KINDS = [
        'signup', 'login', 'logout', 'verification', 'profile_updated',
        'address_added', 'address_updated', 'address_removed',
        'saved_added', 'saved_removed', 'settings_updated',
        'deactivated', 'reactivated',
        'support_opened', 'support_replied', 'support_closed',
    ];

    protected $connection = 'tenant_shared';

    protected $fillable = ['tenant_id', 'user_id', 'kind', 'label'];

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
     * @return Factory<CustomerActivity>
     */
    protected static function newFactory(): Factory
    {
        return CustomerActivityFactory::new();
    }
}
