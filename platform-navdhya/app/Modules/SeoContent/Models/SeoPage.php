<?php

namespace App\Modules\SeoContent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $slug
 * @property string $cluster
 * @property bool $noindex
 * @property string|null $title
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property mixed $page_json
 * @property string|null $search_text
 * @property string|null $content_hash
 * @property \Carbon\Carbon|null $automation_generated_at
 * @property string|null $search_text
 */
class SeoPage extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'seo_pages';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'slug',
        'cluster',
        'noindex',
        'title',
        'meta_title',
        'meta_description',
        'last_updated',
        'automation_generated_at',
        'content_hash',
        'page_json',
        'search_text',
    ];

    protected $casts = [
        'noindex' => 'boolean',
        'last_updated' => 'date',
        'automation_generated_at' => 'datetime',
        'page_json' => 'json',
    ];

    /** @return BelongsTo<SeoCluster, $this> */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(SeoCluster::class, 'cluster', 'cluster');
    }

    /**
     * Scope: public (non-noindex) pages.
     */
    /**
     * @param Builder<SeoPage> $query
     * @return Builder<SeoPage>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('noindex', false);
    }

    /**
     * Scope: by cluster.
     */
    /**
     * @param Builder<SeoPage> $query
     * @return Builder<SeoPage>
     */
    public function scopeInCluster(Builder $query, string $cluster): Builder
    {
        return $query->where('cluster', $cluster);
    }
}
