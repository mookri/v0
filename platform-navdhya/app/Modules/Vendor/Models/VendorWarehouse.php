<?php

namespace App\Modules\Vendor\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vendor warehouse location (tenant_shared, D1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $vendor_id
 * @property string $code
 * @property string $name
 * @property string $city
 * @property string $state
 * @property array<string, mixed>|null $address_json
 * @property bool $is_default
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class VendorWarehouse extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'code',
        'name',
        'city',
        'state',
        'address_json',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'address_json' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
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
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * @return HasMany<VendorInventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(VendorInventory::class, 'location_id');
    }

    /**
     * @return array{id: string, vendor_id: string, code: string, name: string, city: string, state: string, is_default: bool, is_active: bool}
     */
    public static function shape(self $row): array
    {
        return [
            'id' => (string) $row->getKey(),
            'vendor_id' => $row->vendor_id,
            'code' => $row->code,
            'name' => $row->name,
            'city' => $row->city,
            'state' => $row->state,
            'is_default' => (bool) $row->is_default,
            'is_active' => (bool) $row->is_active,
        ];
    }
}
