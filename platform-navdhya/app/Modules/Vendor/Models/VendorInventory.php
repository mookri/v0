<?php

namespace App\Modules\Vendor\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Vendor inventory row (tenant_shared): on-hand stock for one vendor SKU.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $vendor_id
 * @property string $sku
 * @property int $on_hand
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
        'on_hand',
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
     * @return array{id: string, object: string, vendor_id: string, sku: string, on_hand: int, created_at: string, updated_at: string}
     */
    public static function shape(self $row): array
    {
        return [
            'id' => (string) $row->getKey(),
            'object' => 'vendor_inventory',
            'vendor_id' => $row->vendor_id,
            'sku' => $row->sku,
            'on_hand' => (int) $row->on_hand,
            'created_at' => (string) $row->created_at,
            'updated_at' => (string) $row->updated_at,
        ];
    }
}
