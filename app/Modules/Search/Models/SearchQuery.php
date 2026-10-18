<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $query_hash
 * @property string $query_text
 * @property string|null $entity_type
 * @property string|null $city
 * @property int $result_count
 * @property bool $zero_results
 * @property \Carbon\Carbon|null $searched_at
 * @property int $count
 * @property int $zero_count
 * @property string|null $trace_id
 * @property int|null $search_version
 */
class SearchQuery extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_queries';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'query_hash',
        'query_text',
        'entity_type',
        'city',
        'result_count',
        'zero_results',
        'searched_at',
    ];

    protected $casts = [
        'zero_results' => 'boolean',
        'searched_at' => 'datetime',
    ];

    public $timestamps = false;
}
