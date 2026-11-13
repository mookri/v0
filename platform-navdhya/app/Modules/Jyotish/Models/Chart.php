<?php

namespace App\Modules\Jyotish\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent model for Jyotish Charts (T-JY03-04).
 * Stores immutable calculated Kundli charts, Lagna, planetary positions, cusps, and vargas.
 */
class Chart extends Model
{
    use BelongsToTenant;
    use HasUlids;
    use SoftDeletes;

    protected $connection = 'tenant_shared';

    protected $table = 'jyotish_charts';

    protected $guarded = [];

    public function getConnectionName(): ?string
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        if (! $context->isResolved()) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }

    protected function casts(): array
    {
        return [
            'lagna_degrees' => 'float',
            'positions_json' => 'array',
            'houses_json' => 'array',
            'vargas_json' => 'array',
        ];
    }

    public function birthProfile(): BelongsTo
    {
        return $this->belongsTo(BirthProfile::class, 'birth_profile_id');
    }
}

