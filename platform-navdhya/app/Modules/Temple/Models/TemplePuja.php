<?php

namespace App\Modules\Temple\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\TemplePujaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temple puja assignment (tenant_shared): links temples to pujas with overrides.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $temple_id
 * @property string $puja_id
 * @property int|null $price_override_minor
 * @property int|null $capacity
 * @property string $slot_type
 * @property int $min_persons
 * @property int $max_persons
 * @property bool $requires_approval
 * @property int $approval_sla_minutes
 * @property string|null $schedule_json
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static TemplePujaFactory factory()
 */
final class TemplePuja extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<TemplePujaFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public const SLOT_TYPE_PUJA = 'PUJA';
    public const SLOT_TYPE_DARSHAN = 'DARSHAN';
    public const SLOT_TYPE_HOMA = 'HOMA';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'temple_id',
        'puja_id',
        'price_override_minor',
        'capacity',
        'slot_type',
        'min_persons',
        'max_persons',
        'requires_approval',
        'approval_sla_minutes',
        'schedule_json',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'schedule_json' => 'array',
            'min_persons' => 'integer',
            'max_persons' => 'integer',
            'requires_approval' => 'boolean',
            'approval_sla_minutes' => 'integer',
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
     * @return BelongsTo<Temple, $this>
     */
    public function temple(): BelongsTo
    {
        return $this->belongsTo(Temple::class, 'temple_id');
    }

    /**
     * @return Factory<TemplePuja>
     */
    protected static function newFactory(): Factory
    {
        return TemplePujaFactory::new();
    }
}
