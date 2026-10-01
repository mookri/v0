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

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'sku', 'name', 'description', 'status',
        'currency', 'price_minor', 'attributes',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'attributes' => 'array',
        ];
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
