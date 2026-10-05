<?php

namespace App\Modules\Vendor\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Vendor order line (tenant_shared): immutable snapshot captured at
 * placement — later catalog edits never rewrite history.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $order_id
 * @property string $sku
 * @property string $name
 * @property int $quantity
 * @property int $unit_minor
 * @property int $line_minor
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\VendorOrderItemFactory factory()
 */
final class VendorOrderItem extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<\Database\Factories\VendorOrderItemFactory> */
    use HasFactory;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'sku',
        'name',
        'quantity',
        'unit_minor',
        'line_minor',
    ];

    /**
     * @return \Database\Factories\VendorOrderItemFactory
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return \Database\Factories\VendorOrderItemFactory::new();
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
     * @return array{id: string, sku: string, name: string, quantity: int, unit_minor: int, line_minor: int}
     */
    public static function shape(self $item): array
    {
        return [
            'id' => (string) $item->getKey(),
            'sku' => $item->sku,
            'name' => $item->name,
            'quantity' => (int) $item->quantity,
            'unit_minor' => (int) $item->unit_minor,
            'line_minor' => (int) $item->line_minor,
        ];
    }
}
