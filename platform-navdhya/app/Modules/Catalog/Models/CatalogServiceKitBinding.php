<?php

namespace App\Modules\Catalog\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Service to Samagri Kit Binding Model (F3).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $service_item_id
 * @property string $kit_item_id
 * @property string $obligation
 * @property int $quantity_per_party
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class CatalogServiceKitBinding extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const OBLIGATION_REQUIRED = 'REQUIRED';
    public const OBLIGATION_OPTIONAL = 'OPTIONAL';
    public const OBLIGATION_INCLUDED = 'INCLUDED';

    protected $connection = 'tenant_shared';

    protected $table = 'catalog_service_kit_bindings';

    protected $fillable = [
        'tenant_id',
        'service_item_id',
        'kit_item_id',
        'obligation',
        'quantity_per_party',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_party' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'service_item_id');
    }

    public function kitItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'kit_item_id');
    }
}

