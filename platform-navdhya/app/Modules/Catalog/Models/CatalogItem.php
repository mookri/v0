<?php

namespace App\Modules\Catalog\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalog item (tenant-scoped sellable).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $vendor_id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string $currency
 * @property int $price_minor
 * @property array<string, mixed>|null $attributes
 */
final class CatalogItem extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<\Database\Factories\CatalogItemFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const KIND_SERVICE = 'SERVICE';

    public const KIND_GOODS = 'GOODS';

    public const KIND_KIT = 'KIT';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'vendor_id', 'category_id', 'sku', 'slug', 'name', 'description', 'status',
        'item_kind', 'currency', 'price_minor', 'base_duration_minutes', 'attributes',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'base_duration_minutes' => 'integer',
            'attributes' => 'array',
        ];
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CatalogCategory::class, 'category_id');
    }

    public function scopeServices(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('item_kind', self::KIND_SERVICE);
    }

    public function scopeKits(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('item_kind', self::KIND_KIT);
    }

    public function scopeGoods(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('item_kind', self::KIND_GOODS);
    }

    /**
     * @return \Database\Factories\CatalogItemFactory
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return \Database\Factories\CatalogItemFactory::new();
    }

    public function getConnectionName(): ?string
    {
        try {
            $context = app(\App\Platform\Tenancy\TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }
}
