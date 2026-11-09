<?php

namespace App\Modules\Temple\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Temple Deity model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $temple_id
 * @property string $name
 * @property string|null $lineage
 * @property string|null $iconography_url
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TempleDeity extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $connection = 'tenant_shared';

    protected $table = 'temple_deities';

    protected $fillable = [
        'tenant_id',
        'temple_id',
        'name',
        'lineage',
        'iconography_url',
        'status',
    ];

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
     * @return HasMany<TempleAartiSchedule, $this>
     */
    public function aartis(): HasMany
    {
        return $this->hasMany(TempleAartiSchedule::class, 'deity_id', 'id');
    }
}
