<?php

namespace App\Modules\Finance\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Finance Document Line Item (Phase 5, Task 5.1.3).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $finance_document_id
 * @property int $line_number
 * @property string|null $sku
 * @property string $description
 * @property int $quantity
 * @property int $unit_price_minor
 * @property int $tax_rate_bps
 * @property int $tax_minor
 * @property int $total_minor
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class FinanceDocumentLine extends Model
{
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'finance_document_id',
        'line_number',
        'sku',
        'description',
        'quantity',
        'unit_price_minor',
        'tax_rate_bps',
        'tax_minor',
        'total_minor',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'integer',
            'unit_price_minor' => 'integer',
            'tax_rate_bps' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
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

