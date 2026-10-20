<?php

namespace App\Modules\SeoContent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $cluster
 * @property string|null $label
 * @property string|null $base_path
 */
class SeoCluster extends Model
{
    protected $connection = 'central';
    protected $table = 'seo_clusters';
    protected $primaryKey = 'cluster';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'cluster',
        'label',
        'base_path',
    ];

    /** @return HasMany<SeoPage, $this> */
    public function pages(): HasMany
    {
        return $this->hasMany(SeoPage::class, 'cluster', 'cluster');
    }
}
