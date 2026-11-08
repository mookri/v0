<?php

namespace App\Modules\Tax\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Tax Code (Phase 3, Task 3.2.1).
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $category
 * @property string $jurisdiction
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TaxCode extends Model
{
    use HasUlids;

    public const CATEGORY_SERVICE = 'SERVICE';
    public const CATEGORY_GOODS = 'GOODS';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'code',
        'name',
        'category',
        'jurisdiction',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getConnectionName(): ?string
    {
        try {
            $context = app(TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }
}

