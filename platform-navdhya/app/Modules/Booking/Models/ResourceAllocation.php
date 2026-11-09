<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Atomic Resource Allocation (Phase 2, Task 2.2.1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $calendar_id
 * @property string|null $booking_id
 * @property string|null $revision_id
 * @property \Illuminate\Support\Carbon $interval_start
 * @property \Illuminate\Support\Carbon $interval_end
 * @property int $units
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string|null $release_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ResourceAllocation extends Model
{
    use HasUlids;

    public const STATUS_HELD = 'HELD';
    public const STATUS_COMMITTED = 'COMMITTED';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_EXPIRED = 'EXPIRED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'calendar_id',
        'booking_id',
        'revision_id',
        'interval_start',
        'interval_end',
        'units',
        'status',
        'expires_at',
        'release_reason',
    ];

    protected function casts(): array
    {
        return [
            'interval_start' => 'datetime',
            'interval_end' => 'datetime',
            'expires_at' => 'datetime',
            'units' => 'integer',
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
