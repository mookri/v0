<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Canonical Package Tier definition (F4).
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $sort
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class CatalogPackageTier extends Model
{
    use HasUlids;

    public const TIER_STANDARD = 'STANDARD';
    public const TIER_PREMIUM = 'PREMIUM';
    public const TIER_COMPLETE = 'COMPLETE';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'code',
        'name',
        'description',
        'sort',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }
}

