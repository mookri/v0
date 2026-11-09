<?php

namespace App\Modules\Temple\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temple Venue Limit model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $temple_id
 * @property string $scope
 * @property string|null $slot_type
 * @property int $limit_units
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TempleVenueLimit extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const SCOPE_CONCURRENT_EVENTS = 'CONCURRENT_EVENTS';
    public const SCOPE_DAILY_HEADCOUNT = 'DAILY_HEADCOUNT';
    public const SCOPE_DAILY_BOOKINGS = 'DAILY_BOOKINGS';
    public const SCOPE_SLOT_TYPE = 'SLOT_TYPE';

    protected $connection = 'tenant_shared';

    protected $table = 'temple_venue_limits';

    protected $fillable = [
        'tenant_id',
        'temple_id',
        'scope',
        'slot_type',
        'limit_units',
    ];

    protected function casts(): array
    {
        return [
            'limit_units' => 'integer',
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
     * @return BelongsTo<Temple, $this>
     */
    public function temple(): BelongsTo
    {
        return $this->belongsTo(Temple::class, 'temple_id', 'id');
    }
}

