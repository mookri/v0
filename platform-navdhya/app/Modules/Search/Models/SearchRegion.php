<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $parent_id
 * @property string $kind
 * @property string $name_en
 * @property string|null $name_dev
 * @property array<string>|null $name_alt_json
 * @property string|null $geohash6
 * @property int|null $lat_milli
 * @property int|null $lng_milli
 * @property string $population_tier
 * @property bool $is_serviceable
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchRegion extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_regions';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'parent_id',
        'kind',
        'name_en',
        'name_dev',
        'name_alt_json',
        'geohash6',
        'lat_milli',
        'lng_milli',
        'population_tier',
        'is_serviceable',
    ];

    protected $casts = [
        'name_alt_json' => 'array',
        'lat_milli' => 'integer',
        'lng_milli' => 'integer',
        'is_serviceable' => 'boolean',
    ];
}
