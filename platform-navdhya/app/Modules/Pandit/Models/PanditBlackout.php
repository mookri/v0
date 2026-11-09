<?php

namespace App\Modules\Pandit\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pandit Blackout model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $pandit_id
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon $ends_at
 * @property string|null $reason
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PanditBlackout extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const TYPE_PERSONAL = 'PERSONAL';
    public const TYPE_TRAVEL = 'TRAVEL';
    public const TYPE_FESTIVAL = 'FESTIVAL';
    public const TYPE_ADMIN_BLOCK = 'ADMIN_BLOCK';

    protected $connection = 'tenant_shared';

    protected $table = 'pandit_blackouts';

    protected $fillable = [
        'tenant_id',
        'pandit_id',
        'starts_at',
        'ends_at',
        'reason',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
     * @return BelongsTo<PanditProfile, $this>
     */
    public function pandit(): BelongsTo
    {
        return $this->belongsTo(PanditProfile::class, 'pandit_id', 'id');
    }
}

