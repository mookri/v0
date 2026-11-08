<?php

namespace App\Modules\Tax\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Tax Computation Snapshot (Phase 3, Task 3.2.1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $quote_id
 * @property string|null $booking_id
 * @property string|null $order_id
 * @property string $jurisdiction
 * @property string $currency
 * @property int $taxable_amount_minor
 * @property int $tax_amount_minor
 * @property int $total_amount_minor
 * @property array<string, mixed> $components_json
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TaxComputation extends Model
{
    use HasUlids;

    public const STATUS_QUOTED = 'QUOTED';
    public const STATUS_COMMITTED = 'COMMITTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'quote_id',
        'booking_id',
        'order_id',
        'jurisdiction',
        'currency',
        'taxable_amount_minor',
        'tax_amount_minor',
        'total_amount_minor',
        'components_json',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'taxable_amount_minor' => 'integer',
            'tax_amount_minor' => 'integer',
            'total_amount_minor' => 'integer',
            'components_json' => 'array',
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

