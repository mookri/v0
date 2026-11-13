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
 * @property string $action
 * @property int|null $dwell_before_click_ms
 * @property \Carbon\Carbon|null $selected_at
 */
class SearchSelection extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_selections';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'trace_id',
        'entity_type',
        'entity_id',
        'position',
        'action',
        'dwell_before_click_ms',
        'selected_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'dwell_before_click_ms' => 'integer',
        'selected_at' => 'datetime',
    ];

    public $timestamps = false;
}
