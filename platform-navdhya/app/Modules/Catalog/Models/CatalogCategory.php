<?php

namespace App\Modules\Catalog\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\IncludesPlatformDefaults;
use App\Platform\Tenancy\PlatformScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hierarchical Catalog Category Model (F1).
 *
 * Implements materialized-path tree with platform defaults (tenant_id NULL)
 * and tenant-specific branches. Cross-dialect unique constraint is enforced
 * via tenancy_key coalesce.
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $tenancy_key
 * @property string|null $parent_id
 * @property string $path
 * @property int $depth
 * @property string $slug
 * @property string $name
 * @property string $kind
 * @property string $provenance
 * @property int $sort
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class CatalogCategory extends Model implements IncludesPlatformDefaults
{
    use BelongsToTenant;
    use HasUlids;
    use SoftDeletes;

    public const KIND_RITUALS = 'RITUALS';
    public const KIND_HAVANS = 'HAVANS';
    public const KIND_SANSKARS = 'SANSKARS';
    public const KIND_ASTROLOGY = 'ASTROLOGY';
    public const KIND_TEMPLE_PUJAS = 'TEMPLE_PUJAS';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const PROVENANCE_AUTHENTIC = 'AUTHENTIC';
    public const PROVENANCE_INFERRED = 'INFERRED';
    public const PROVENANCE_SEEDED = 'SEEDED';

    protected $connection = 'tenant_shared';

    protected $table = 'catalog_categories';

    protected $fillable = [
        'tenant_id',
        'tenancy_key',
        'parent_id',
        'path',
        'depth',
        'slug',
        'name',
        'kind',
        'provenance',
        'sort',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (CatalogCategory $category): void {
            $category->tenancy_key = $category->tenant_id ?? '__PLATFORM_GLOBAL__';

            if (! empty($category->parent_id)) {
                $parent = PlatformScope::run(
                    'catalog_category_parent_lookup',
                    static fn () => static::query()->find($category->parent_id),
                    tenantId: $category->tenant_id,
                );
                if ($parent !== null) {
                    $category->depth = $parent->depth + 1;
                    $category->path = rtrim($parent->path, '/') . '/' . ($category->id ?? '') . '/';
                    $category->kind = $parent->kind;
                }
            } else {
                $category->depth = 0;
                $category->path = '/' . ($category->id ?? '') . '/';
            }
        });

        static::created(function (CatalogCategory $category): void {
            // Ensure path includes self ID on initial creation if empty
            if ($category->path === '//' || str_ends_with($category->path, '//')) {
                $basePath = ! empty($category->parent_id)
                    ? rtrim((string) PlatformScope::run(
                        'catalog_category_parent_path_lookup',
                        static fn () => static::query()->where('id', $category->parent_id)->value('path'),
                        tenantId: $category->tenant_id,
                    ), '/')
                    : '';
                $category->path = ($basePath !== '' ? $basePath : '') . '/' . $category->id . '/';
                $category->saveQuietly();
            }
        });
    }

    public function includesPlatformDefaults(): bool
    {
        return true;
    }

    public function isPlatformDefault(): bool
    {
        return $this->tenant_id === null;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogItem::class, 'category_id');
    }
}

