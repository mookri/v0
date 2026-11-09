<?php

namespace App\Modules\Pandit\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pandit assignment rules model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $pandit_id
 * @property list<string>|null $auto_accept_skus
 * @property int|null $max_distance_km
 * @property int $response_sla_hours
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PanditAssignmentRule extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'pandit_assignment_rules';

    protected $fillable = [
        'tenant_id',
        'pandit_id',
        'auto_accept_skus',
        'max_distance_km',
        'response_sla_hours',
    ];

    protected function casts(): array
    {
        return [
            'auto_accept_skus' => 'array',
            'max_distance_km' => 'integer',
            'response_sla_hours' => 'integer',
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

