<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $tenant_id
 * @property string $locale
 * @property string $scheme
 * @property string $term_key
 * @property int $key_len
 * @property int $doc_freq
 * @property int $popularity
 * @property int $click_count
 * @property string|null $first_document_id
 * @property int $generation
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchDictionary extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_dictionary';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'locale',
        'scheme',
        'term_key',
        'key_len',
        'doc_freq',
        'popularity',
        'click_count',
        'first_document_id',
        'generation',
    ];

    protected $casts = [
        'key_len' => 'integer',
        'doc_freq' => 'integer',
        'popularity' => 'integer',
        'click_count' => 'integer',
        'generation' => 'integer',
    ];

    /**
     * @return HasMany<SearchPosting, $this>
     */
    public function postings(): HasMany
    {
        return $this->hasMany(SearchPosting::class, 'dictionary_id');
    }

    /**
     * @return BelongsTo<SearchDocument, $this>
     */
    public function firstDocument(): BelongsTo
    {
        return $this->belongsTo(SearchDocument::class, 'first_document_id');
    }
}
