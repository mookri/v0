<?php

declare(strict_types=1);

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Arrival evidence record for a booking assignment (TS-01, ADR-0060).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $booking_id
 * @property string|null $assignment_id
 * @property string $pandit_id
 * @property \Carbon\CarbonImmutable|\Illuminate\Support\Carbon $arrived_at
 * @property string $recorded_by_actor_id
 * @property string $source_channel
 * @property \Carbon\CarbonImmutable|\Illuminate\Support\Carbon|null $corroborated_at
 * @property string|null $corroborated_by_id
 * @property string|null $corroboration_channel
 * @property string $dispute_state
 * @property int|null $delta_minutes
 */
final class PanditArrival extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const SOURCE_PARTNER_APP = 'partner_app';

    public const SOURCE_DEVOTEE_CORROBORATION = 'devotee_corroboration';

    public const SOURCE_COORDINATOR = 'coordinator';

    public const SOURCE_OPS_OVERRIDE = 'ops_override';

    public const DISPUTE_NONE = 'NONE';

    public const DISPUTE_CONTESTED = 'CONTESTED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'assignment_id',
        'pandit_id',
        'arrived_at',
        'recorded_by_actor_id',
        'source_channel',
        'corroborated_at',
        'corroborated_by_id',
        'corroboration_channel',
        'dispute_state',
        'delta_minutes',
    ];

    protected function casts(): array
    {
        return [
            'arrived_at' => 'datetime',
            'corroborated_at' => 'datetime',
            'delta_minutes' => 'integer',
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
}

