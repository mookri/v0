<?php

namespace App\Modules\Pandit\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pandit ritual service eligibility model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $pandit_id
 * @property string $service_sku
 * @property bool $is_qualified
 * @property string $qualification_level
 * @property \Illuminate\Support\Carbon|null $certified_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PanditServiceEligibility extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'pandit_service_eligibility';

    protected $fillable = [
        'tenant_id',
        'pandit_id',
        'service_sku',
        'is_qualified',
        'qualification_level',
        'certified_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_qualified' => 'boolean',
            'certified_at' => 'datetime',
            'expires_at' => 'datetime',
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

