<?php

namespace App\Modules\Temple\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temple Booking Approval model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $booking_id
 * @property string $temple_id
 * @property string|null $revision_id
 * @property string $state
 * @property string $required_role
 * @property \Illuminate\Support\Carbon $sla_expires_at
 * @property \Illuminate\Support\Carbon $hard_expires_at
 * @property int|null $decision_by
 * @property string|null $decision_reason
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TempleBookingApproval extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATE_PENDING = 'PENDING';
    public const STATE_APPROVED = 'APPROVED';
    public const STATE_DECLINED = 'DECLINED';
    public const STATE_ESCALATED = 'ESCALATED';
    public const STATE_EXPIRED = 'EXPIRED';

    protected $connection = 'tenant_shared';

    protected $table = 'temple_booking_approvals';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'temple_id',
        'revision_id',
        'state',
        'required_role',
        'sla_expires_at',
        'hard_expires_at',
        'decision_by',
        'decision_reason',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'sla_expires_at' => 'datetime',
            'hard_expires_at' => 'datetime',
            'decision_by' => 'integer',
            'decided_at' => 'datetime',
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
