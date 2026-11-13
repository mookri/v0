<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $trace_id
 * @property string $entity_type
 * @property string $entity_id
 * @property int $position
 * @property int $page_offset
 * @property float|null $score
 * @property int $dwell_ms
 * @property float $viewport_ratio
 * @property \Carbon\Carbon|null $impressed_at
 */
class SearchImpression extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_impressions';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'trace_id',
        'entity_type',
        'entity_id',
        'position',
        'page_offset',
        'score',
        'dwell_ms',
        'viewport_ratio',
        'impressed_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'page_offset' => 'integer',
        'score' => 'float',
        'dwell_ms' => 'integer',
        'viewport_ratio' => 'float',
        'impressed_at' => 'datetime',
    ];

    public $timestamps = false;
}
