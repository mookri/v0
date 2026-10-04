<?php

namespace App\Modules\Pandit\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\PanditAvailabilityFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pandit availability (tenant_shared): recurring weekly schedule + one-off overrides.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $pandit_id
 * @property int $day_of_week
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property int $slot_duration_minutes
 * @property bool $is_recurring
 * @property \Illuminate\Support\Carbon|null $specific_date
 */
final class PanditAvailability extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<PanditAvailabilityFactory> */
    use HasFactory;

    /** Str::plural('pandit_availability') would be 'pandit_availabilities'. */
    protected $table = 'pandit_availability';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'pandit_id', 'day_of_week', 'start_time', 'end_time',
        'slot_duration_minutes', 'is_recurring', 'specific_date',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'slot_duration_minutes' => 'integer',
            'is_recurring' => 'boolean',
            'specific_date' => 'date',
        ];
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
     * @return Factory<PanditAvailability>
     */
    protected static function newFactory(): Factory
    {
        return PanditAvailabilityFactory::new();
    }
}
