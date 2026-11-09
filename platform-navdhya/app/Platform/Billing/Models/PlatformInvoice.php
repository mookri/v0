<?php

namespace App\Platform\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform Invoice for SaaS Subscription (Phase 6, Task 6.1.3).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $subscription_id
 * @property string $invoice_number
 * @property int $subtotal_minor
 * @property int $tax_minor
 * @property int $total_minor
 * @property string $currency
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PlatformInvoice extends Model
{
    use HasUlids;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_VOID = 'VOID';

    protected $connection = 'central';

    protected $table = 'platform_invoices';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'invoice_number',
        'subtotal_minor',
        'tax_minor',
        'total_minor',
        'currency',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
