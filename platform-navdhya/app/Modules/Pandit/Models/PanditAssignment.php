<?php

namespace App\Modules\Pandit\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\PanditAssignmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pandit assignment (tenant_shared): links pandits to bookings.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $pandit_id
 * @property string $booking_id
 * @property string $status
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $service_started_at
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property string|null $decline_reason
 * @property array<string, mixed>|null $checklist_json
 * @property int|null $compensation_minor
 * @property int $version
 */
final class PanditAssignment extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<PanditAssignmentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_DECLINED = 'DECLINED';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'pandit_id', 'booking_id', 'status',
        'assigned_at', 'confirmed_at', 'service_started_at', 'declined_at',
        'decline_reason', 'checklist_json', 'compensation_minor', 'version',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'service_started_at' => 'datetime',
            'declined_at' => 'datetime',
            'checklist_json' => 'array',
            'compensation_minor' => 'integer',
            'version' => 'integer',
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
     * @return BelongsTo<PanditProfile, $this>
     */
    public function pandit(): BelongsTo
    {
        return $this->belongsTo(PanditProfile::class, 'pandit_id');
    }

    // No booking() relation: booking rows belong to the Booking module and
    // are resolved through the ReadsBookings port (deptrac: contracts only).

    /**
     * @return Factory<PanditAssignment>
     */
    protected static function newFactory(): Factory
    {
        return PanditAssignmentFactory::new();
    }
}
