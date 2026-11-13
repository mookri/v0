<?php

namespace App\Modules\Pandit\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pandit geographic service area model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $pandit_id
 * @property string $region_id
 * @property string $region_name
 * @property bool $is_primary
 * @property int $max_distance_km
 * @property int $travel_fee_minor
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PanditServiceArea extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'pandit_service_areas';

    protected $fillable = [
        'tenant_id',
        'pandit_id',
        'region_id',
        'region_name',
        'is_primary',
        'max_distance_km',
        'travel_fee_minor',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'max_distance_km' => 'integer',
            'travel_fee_minor' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PanditProfile, $this>
     */
    public function pandit(): BelongsTo
    {
        return $this->belongsTo(PanditProfile::class, 'pandit_id');
    }
}

