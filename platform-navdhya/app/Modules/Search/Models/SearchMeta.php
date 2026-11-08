<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property string|null $value
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchMeta extends Model
{
    protected $connection = 'central';
    protected $table = 'search_meta';
    protected $primaryKey = 'key';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'updated_at'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
