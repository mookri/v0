<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

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
        'card_json',
        'status',
        'indexed_at',
    ];

    protected $casts = [
        'card_json' => 'json',
        'indexed_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeForTenant($query, ?string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }
}
