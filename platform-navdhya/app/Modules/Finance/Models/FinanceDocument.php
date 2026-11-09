<?php

namespace App\Modules\Finance\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Finance Document (Phase 5, Task 5.1.2).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $document_number
 * @property string $document_type
 * @property string|null $booking_id
 * @property string|null $payment_operation_id
 * @property int|null $buyer_id
 * @property string $currency
 * @property int $subtotal_minor
 * @property int $tax_minor
 * @property int $total_minor
 * @property string $status
 * @property \Illuminate\Support\Carbon $issued_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class FinanceDocument extends Model
{
    use HasUlids;

    public const TYPE_INVOICE = 'INVOICE';
    public const TYPE_CREDIT_NOTE = 'CREDIT_NOTE';
    public const TYPE_DEBIT_NOTE = 'DEBIT_NOTE';
    public const TYPE_RECEIPT = 'RECEIPT';

    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_VOID = 'VOID';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'document_number',
        'document_type',
        'booking_id',
        'payment_operation_id',
        'buyer_id',
        'currency',
        'subtotal_minor',
        'tax_minor',
        'total_minor',
        'status',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'buyer_id' => 'integer',
            'subtotal_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'issued_at' => 'datetime',
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

    /**
     * @return HasMany<FinanceDocumentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(FinanceDocumentLine::class, 'finance_document_id');
    }
}
