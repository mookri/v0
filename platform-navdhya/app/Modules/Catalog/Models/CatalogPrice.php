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
 * @property string|null $pandit_id
 * @property string|null $package_tier_code
 * @property string|null $qualification_tier_code
 * @property int $price_minor
 * @property int $version
 * @property bool $is_current
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 */
final class CatalogPrice extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'sku', 'market', 'currency', 'pandit_id',
        'package_tier_code', 'qualification_tier_code',
        'price_minor', 'version', 'is_current',
        'effective_from', 'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'version' => 'integer',
            'is_current' => 'boolean',
            // Pandit ids are ULIDs: an integer cast destroyed the id.

            'pandit_id' => 'string',
            'package_tier_code' => 'string',
            'qualification_tier_code' => 'string',
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
