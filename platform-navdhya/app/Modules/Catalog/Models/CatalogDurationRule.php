<?php

namespace App\Modules\Catalog\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catalog Duration Rule Model (F2).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $item_id
 * @property string $scope
 * @property string $scope_key
 * @property array<string, mixed> $rule
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class CatalogDurationRule extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const SCOPE_BASE = 'BASE';
    public const SCOPE_PER_PARTICIPANT = 'PER_PARTICIPANT';
    public const SCOPE_PACKAGE_TIER = 'PACKAGE_TIER';
    public const SCOPE_TRADITION = 'TRADITION';

    protected $connection = 'tenant_shared';

    protected $table = 'catalog_duration_rules';

    protected $fillable = [
        'tenant_id',
        'item_id',
        'scope',
        'scope_key',
        'rule',
    ];

    protected function casts(): array
    {
        return [
            'rule' => 'array',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'item_id');
    }
}

