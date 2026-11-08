<?php

namespace App\Modules\Finance\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Double-Entry Subledger Entry (Phase 5, Task 5.1.5).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $journal_id
 * @property string $account_code
 * @property string $entry_direction
 * @property int $amount_minor
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class LedgerEntry extends Model
{
    use HasUlids;

    public const DIRECTION_DEBIT = 'DEBIT';
    public const DIRECTION_CREDIT = 'CREDIT';

    // Standard Chart of Accounts
    public const ACCOUNT_GATEWAY_CLEARING = '1010';
    public const ACCOUNT_CUSTOMER_ESCROW = '2010';
    public const ACCOUNT_AP_PANDIT = '2020';
    public const ACCOUNT_AP_VENDOR = '2030';
    public const ACCOUNT_TAX_PAYABLE = '2100';
    public const ACCOUNT_TDS_PAYABLE = '2110';
    public const ACCOUNT_PLATFORM_REVENUE = '4010';
    public const ACCOUNT_PAYMENT_PROCESSING_FEE = '5010';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'journal_id',
        'account_code',
        'entry_direction',
        'amount_minor',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
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

