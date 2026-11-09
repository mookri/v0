<?php

namespace App\Modules\Finance\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Finance Sequence (Phase 5, Task 5.1.1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $sequence_key
 * @property string $prefix
 * @property int $current_number
 * @property int $year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class FinanceSequence extends Model
{
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'sequence_key',
        'prefix',
        'current_number',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'current_number' => 'integer',
            'year' => 'integer',
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
