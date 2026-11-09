<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Vendor Stock Reservation (Phase 2, Task 2.2.4).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $vendor_id
 * @property string $sku
 * @property string|null $booking_id
 * @property string|null $revision_id
 * @property int $units
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class StockReservation extends Model
{
    use HasUlids;

    public const STATUS_HELD = 'HELD';
    public const STATUS_COMMITTED = 'COMMITTED';
    public const STATUS_CONSUMED = 'CONSUMED';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_EXPIRED = 'EXPIRED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'sku',
        'booking_id',
        'revision_id',
        'units',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'units' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function getConnectionName(): ?string
    {
        try {
            $context = app(TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }
}
