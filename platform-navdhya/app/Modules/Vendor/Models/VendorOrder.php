<?php

namespace App\Modules\Vendor\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Vendor order (tenant_shared): per-vendor sale with a linear lifecycle.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $vendor_id
 * @property string $reference
 * @property string|null $customer_name
 * @property string $status
 * @property string $currency
 * @property int $total_minor
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\VendorOrderFactory factory()
 */
final class VendorOrder extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<\Database\Factories\VendorOrderFactory> */
    use HasFactory;

    public const STATUS_NEW = 'NEW';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_DISPATCHED = 'DISPATCHED';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'reference',
        'customer_name',
        'status',
        'currency',
        'total_minor',
    ];

    /**
     * @return \Database\Factories\VendorOrderFactory
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return \Database\Factories\VendorOrderFactory::new();
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
     * Base wire shape; order detail/management responses append `items`.
     *
     * @return array{id: string, object: string, vendor_id: string, reference: string, customer_name: string|null, status: string, currency: string, total_minor: int, created_at: string, updated_at: string}
     */
    public static function shape(self $order): array
    {
        return [
            'id' => (string) $order->getKey(),
            'object' => 'vendor_order',
            'vendor_id' => $order->vendor_id,
            'reference' => $order->reference,
            'customer_name' => $order->customer_name,
            'status' => $order->status,
            'currency' => $order->currency,
            'total_minor' => (int) $order->total_minor,
            'created_at' => (string) $order->created_at,
            'updated_at' => (string) $order->updated_at,
        ];
    }
}
