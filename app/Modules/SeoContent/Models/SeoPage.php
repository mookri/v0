<?php

namespace App\Modules\SeoContent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(SeoCluster::class, 'cluster', 'cluster');
    }

    /**
     * Scope: public (non-noindex) pages.
     */
    public function scopePublic($query)
    {
        return $query->where('noindex', false);
    }

    /**
     * Scope: by cluster.
     */
    public function scopeInCluster($query, string $cluster)
    {
        return $query->where('cluster', $cluster);
    }
}
