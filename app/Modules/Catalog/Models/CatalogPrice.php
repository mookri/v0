<?php

namespace App\Modules\Catalog\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Effective-dated price row (minor units, never floats).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $sku
 * @property string $market
 * @property string $currency
 * @property int $price_minor
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 */
final class CatalogPrice extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'sku', 'market', 'currency', 'price_minor',
        'effective_from', 'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
        ];
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
