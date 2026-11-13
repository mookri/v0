<?php

namespace App\Modules\Jyotish\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent model for devotee Jyotish birth profiles (T-JY01-02).
 * Stores normalized WGS84 coordinates, historical timezones, and astronomical timestamps.
 */
class BirthProfile extends Model
{
    use BelongsToTenant;
    use HasUlids;
    use SoftDeletes;

    protected $connection = 'tenant_shared';

    protected $table = 'jyotish_birth_profiles';

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
            'latitude' => 'float',
            'longitude' => 'float',
            'elevation_m' => 'integer',
            'local_datetime' => 'datetime',
            'utc_at_us' => 'integer',
            'jd_utc' => 'float',
            'jd_tt' => 'float',
            'delta_t_s' => 'float',
            'provenance_json' => 'array',
            'metadata_json' => 'array',
        ];
    }
}

