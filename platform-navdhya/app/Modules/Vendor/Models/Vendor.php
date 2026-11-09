<?php

namespace App\Modules\Vendor\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Vendor profile (tenant_shared): partner-run storefront bound to one
 * APPROVED partner-vendor application and one owning user. Connection
 * follows the ambient tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $application_id
 * @property int $owner_user_id
 * @property string $name
 * @property string|null $description
 * @property string|null $city
 * @property string|null $state
 * @property string $status
 * @property string $verification_status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\VendorFactory factory()
 */
final class Vendor extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory;

    public const APPLICATION_TRACK = 'partner-vendor';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const VERIFICATION_PENDING = 'PENDING';
    public const VERIFICATION_VERIFIED = 'VERIFIED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'application_id',
        'owner_user_id',
        'name',
        'description',
        'city',
        'state',
        'status',
        'verification_status',
    ];

    /**
     * @return \Database\Factories\VendorFactory
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return \Database\Factories\VendorFactory::new();
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<VendorWarehouse, $this>
     */
    public function warehouses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VendorWarehouse::class, 'vendor_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<VendorKitComponent, $this>
     */
    public function kitComponents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VendorKitComponent::class, 'vendor_id');
    }

    /**
     * Wire shape (partner and admin surfaces share it).
     *
     * @return array{id: string, object: string, application_id: string, owner_user_id: int, name: string, description: string|null, city: string|null, state: string|null, status: string, verification_status: string, created_at: string, updated_at: string}
     */
    public static function shape(self $vendor): array
    {
        return [
            'id' => (string) $vendor->getKey(),
            'object' => 'vendor',
            'application_id' => $vendor->application_id,
            'owner_user_id' => (int) $vendor->owner_user_id,
            'name' => $vendor->name,
            'description' => $vendor->description,
            'city' => $vendor->city,
            'state' => $vendor->state,
            'status' => $vendor->status,
            'verification_status' => $vendor->verification_status,
            'created_at' => (string) $vendor->created_at,
            'updated_at' => (string) $vendor->updated_at,
        ];
    }
}
