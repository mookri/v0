<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

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
