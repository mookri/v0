<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $dictionary_id
 * @property string $search_document_id
 * @property string $field
 * @property float $field_weight
 * @property int $tf
 * @property array<int, int>|null $positions_json
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchPosting extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_postings';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'dictionary_id',
        'search_document_id',
        'field',
        'field_weight',
        'tf',
        'positions_json',
    ];

    protected $casts = [
        'field_weight' => 'float',
        'tf' => 'integer',
        'positions_json' => 'array',
    ];

    /**
     * @return BelongsTo<SearchDictionary, $this>
     */
    public function dictionary(): BelongsTo
    {
        return $this->belongsTo(SearchDictionary::class, 'dictionary_id');
    }

    /**
     * @return BelongsTo<SearchDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SearchDocument::class, 'search_document_id');
    }
}
