<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $term
 * @property string $synonym
 * @property string $locale
 * @property float $weight
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchSynonym extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_synonyms';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'term',
        'synonym',
        'locale',
        'weight',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    public $timestamps = true;
}
