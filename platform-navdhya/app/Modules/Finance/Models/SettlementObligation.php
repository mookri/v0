<?php

namespace App\Modules\Finance\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Settlement Obligation (Phase 5, Task 5.1.6).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $beneficiary_type
 * @property string $beneficiary_id
 * @property string|null $booking_id
 * @property int $gross_amount_minor
 * @property int $commission_minor
 * @property int $tax_withheld_minor
 * @property int $net_payable_minor
 * @property string $currency
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class SettlementObligation extends Model
{
    use HasUlids;

    public const BENEFICIARY_PANDIT = 'PANDIT';
    public const BENEFICIARY_TEMPLE = 'TEMPLE';
    public const BENEFICIARY_VENDOR = 'VENDOR';
    public const BENEFICIARY_PLATFORM = 'PLATFORM';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'beneficiary_type',
        'beneficiary_id',
        'booking_id',
        'gross_amount_minor',
        'commission_minor',
        'tax_withheld_minor',
        'net_payable_minor',
        'currency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount_minor' => 'integer',
            'commission_minor' => 'integer',
            'tax_withheld_minor' => 'integer',
            'net_payable_minor' => 'integer',
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

