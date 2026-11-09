<?php

namespace App\Modules\Pandit\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pandit Vedic Shakha reference (tenant_shared).
 *
 * @property string $code
 * @property string $veda
 * @property string $name
 * @property bool $is_active
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PanditShakha extends Model
{
    protected $connection = 'tenant_shared';

    protected $table = 'pandit_shakhas';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'veda',
        'name',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
     * @return HasMany<PanditProfile, $this>
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(PanditProfile::class, 'shakha_code', 'code');
    }
}

