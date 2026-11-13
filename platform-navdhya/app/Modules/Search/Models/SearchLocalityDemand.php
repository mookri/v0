<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $region_id
 * @property string $region_name
 * @property string|null $entity_type
 * @property int $total_searches
 * @property int $empty_searches
 * @property float $unserved_rate
 * @property \Carbon\Carbon|null $period_date
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchLocalityDemand extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_locality_demand';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'region_id',
        'region_name',
        'entity_type',
        'total_searches',
        'empty_searches',
        'unserved_rate',
        'period_date',
    ];

    protected $casts = [
        'total_searches' => 'integer',
        'empty_searches' => 'integer',
        'unserved_rate' => 'float',
        'period_date' => 'date',
    ];
}

