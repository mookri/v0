<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Resource Calendar for concurrency and locking (Phase 2, Task 2.2.1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $resource_type
 * @property string $resource_id
 * @property string $timezone
 * @property int $version
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ResourceCalendar extends Model
{
    use HasUlids;

    public const TYPE_PANDIT = 'PANDIT';
    public const TYPE_TEMPLE = 'TEMPLE';
    public const TYPE_STOCK = 'STOCK';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'resource_type',
        'resource_id',
        'timezone',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
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

    /**
     * @return HasMany<ResourceAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(ResourceAllocation::class, 'calendar_id');
    }
}

