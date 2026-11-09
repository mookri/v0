<?php

namespace App\Modules\Temple\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temple Aarti Schedule model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $temple_id
 * @property string|null $deity_id
 * @property int $day_of_week
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property bool $blocks_darshan
 * @property bool $blocks_homa
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TempleAartiSchedule extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'temple_aarti_schedules';

    protected $fillable = [
        'tenant_id',
        'temple_id',
        'deity_id',
        'day_of_week',
        'start_time',
        'end_time',
        'blocks_darshan',
        'blocks_homa',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'blocks_darshan' => 'boolean',
            'blocks_homa' => 'boolean',
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

    /**
     * @return BelongsTo<TempleDeity, $this>
     */
    public function deity(): BelongsTo
    {
        return $this->belongsTo(TempleDeity::class, 'deity_id', 'id');
    }
}
