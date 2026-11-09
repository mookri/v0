<?php

namespace App\Modules\Vendor\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Samagri Shipment (tenant_shared, D1/D5).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $order_id
 * @property string $status
 * @property string|null $carrier_name
 * @property string|null $tracking_code
 * @property \Illuminate\Support\Carbon|null $dispatched_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property string|null $pod_note
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class Shipment extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATUS_PACKED = 'PACKED';
    public const STATUS_HANDOFF = 'HANDOFF';
    public const STATUS_IN_TRANSIT = 'IN_TRANSIT';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_RETURNED = 'RETURNED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'status',
        'carrier_name',
        'tracking_code',
        'dispatched_at',
        'delivered_at',
        'pod_note',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
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
     * @return BelongsTo<VendorOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class, 'order_id');
    }

    /**
     * @return HasMany<ShipmentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class, 'shipment_id');
    }

    /**
     * @return array{id: string, order_id: string, status: string, carrier_name: string|null, tracking_code: string|null, dispatched_at: string|null, delivered_at: string|null, pod_note: string|null}
     */
    public static function shape(self $row): array
    {
        return [
            'id' => (string) $row->getKey(),
            'order_id' => $row->order_id,
            'status' => $row->status,
            'carrier_name' => $row->carrier_name,
            'tracking_code' => $row->tracking_code,
            'dispatched_at' => $row->dispatched_at?->toIso8601String(),
            'delivered_at' => $row->delivered_at?->toIso8601String(),
            'pod_note' => $row->pod_note,
        ];
    }
}
