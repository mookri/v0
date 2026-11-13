<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $pattern_type
 * @property string $pattern_key
 * @property int $sample_count
 * @property int $success_count
 * @property int $empty_count
 * @property array<string, mixed>|null $metrics_json
 * @property \Carbon\Carbon|null $period_start
 * @property \Carbon\Carbon|null $period_end
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchQueryPattern extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_query_patterns';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'pattern_type',
        'pattern_key',
        'sample_count',
        'success_count',
        'empty_count',
        'metrics_json',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'sample_count' => 'integer',
        'success_count' => 'integer',
        'empty_count' => 'integer',
        'metrics_json' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];
}

