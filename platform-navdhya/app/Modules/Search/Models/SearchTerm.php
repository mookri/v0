<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $term
 * @property string $normalized
 * @property string $kind
 * @property float $weight
 * @property string|null $search_document_id
 * @property string|null $title
 * @property string|null $url
 * @property string|null $city
 * @property string|null $tenant_id
 * @property string $locale
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchTerm extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_terms';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'term',
        'normalized',
        'kind',
        'weight',
        'search_document_id',
        'title',
        'url',
        'city',
        'tenant_id',
        'locale',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    public $timestamps = true;

    /** @return BelongsTo<SearchDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SearchDocument::class, 'search_document_id');
    }
}
