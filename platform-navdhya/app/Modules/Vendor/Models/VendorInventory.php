<?php

namespace App\Modules\Vendor\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vendor inventory row (tenant_shared, D1): on-hand and reserved stock for one vendor SKU.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $vendor_id
 * @property string $sku
 * @property string|null $location_id
 * @property int $on_hand
 * @property int $reserved
 * @property int $version
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\VendorInventoryFactory factory()
 */
final class VendorInventory extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<\Database\Factories\VendorInventoryFactory> */
    use HasFactory;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'sku',
        'location_id',
        'on_hand',
        'reserved',
        'version',
    ];

    protected $casts = [
        'on_hand' => 'integer',
        'reserved' => 'integer',
        'version' => 'integer',
    ];

    /**
     * @return \Database\Factories\VendorInventoryFactory
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return \Database\Factories\VendorInventoryFactory::new();
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
     * @return BelongsTo<VendorWarehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(VendorWarehouse::class, 'location_id');
    }

    /**
     * Available physical units not committed to active reservations.
     */
    public function available(): int
    {
        return max(0, (int) $this->on_hand - (int) $this->reserved);
    }

    /**
     * @return array{id: string, object: string, vendor_id: string, sku: string, location_id: string|null, on_hand: int, reserved: int, available: int, version: int, created_at: string, updated_at: string}
     */
    public static function shape(self $row): array
    {
        return [
            'id' => (string) $row->getKey(),
            'object' => 'vendor_inventory',
            'vendor_id' => $row->vendor_id,
            'sku' => $row->sku,
            'location_id' => $row->location_id,
            'on_hand' => (int) $row->on_hand,
            'reserved' => (int) $row->reserved,
            'available' => $row->available(),
            'version' => (int) $row->version,
            'created_at' => (string) $row->created_at,
            'updated_at' => (string) $row->updated_at,
        ];
    }
}
