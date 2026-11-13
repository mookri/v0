<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $trace_id
 * @property string|null $session_id
 * @property int|null $user_id
 * @property string $surface
 * @property string $query_text_raw
 * @property string $query_text_norm
 * @property string $script_family
 * @property string|null $region_code
 * @property string|null $plan_type
 * @property array<string, mixed>|null $plan_json
 * @property int $result_count
 * @property string|null $zero_result_reason
 * @property int|null $generation
 * @property int $stage_parse_ms
 * @property int $stage_recall_ms
 * @property int $stage_rank_ms
 * @property int $stage_total_ms
 * @property \Carbon\Carbon|null $searched_at
 */
class SearchQueryEvent extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_query_events';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'trace_id',
        'session_id',
        'user_id',
        'surface',
        'query_text_raw',
        'query_text_norm',
        'script_family',
        'region_code',
        'plan_type',
        'plan_json',
        'result_count',
        'zero_result_reason',
        'generation',
        'stage_parse_ms',
        'stage_recall_ms',
        'stage_rank_ms',
        'stage_total_ms',
        'searched_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'plan_json' => 'array',
        'result_count' => 'integer',
        'generation' => 'integer',
        'stage_parse_ms' => 'integer',
        'stage_recall_ms' => 'integer',
        'stage_rank_ms' => 'integer',
        'stage_total_ms' => 'integer',
        'searched_at' => 'datetime',
    ];

    public $timestamps = false;
}
