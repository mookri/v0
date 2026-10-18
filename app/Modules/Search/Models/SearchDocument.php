<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $entity_type
 * @property string $entity_id
 * @property string|null $tenant_id
 * @property string $slug
 * @property string $url_path
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $body
 * @property string|null $image_url
 * @property string|null $city
 * @property int $bookings_30d
 * @property int $views_7d
 * @property string $locale
 * @property \Carbon\Carbon|null $popularity_updated_at
 * @property mixed $card_json
 * @property string $status
 * @property \Carbon\Carbon|null $indexed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchDocument extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_documents';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'tenant_id',
        'slug',
        'url_path',
        'title',
        'subtitle',
        'body',
        'image_url',
        'city',
        'bookings_30d',
        'views_7d',
        'locale',
        'popularity_updated_at',
        'card_json',
        'status',
        'indexed_at',
    ];

    protected $casts = [
        'card_json' => 'json',
        'indexed_at' => 'datetime',
        'popularity_updated_at' => 'datetime',
        'bookings_30d' => 'integer',
        'views_7d' => 'integer',
    ];

    /**
     * @param Builder<SearchDocument> $query
     * @return Builder<SearchDocument>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * @param Builder<SearchDocument> $query
     * @return Builder<SearchDocument>
     */
    public function scopeForTenant(Builder $query, ?string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * @param Builder<SearchDocument> $query
     * @return Builder<SearchDocument>
     */
    public function scopeForEntityType(Builder $query, string $entityType): Builder
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * @param Builder<SearchDocument> $query
     * @return Builder<SearchDocument>
     */
    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }
}
