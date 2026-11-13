<?php

namespace App\Modules\Finance\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Closed/open financial accounting period lock per tenant (`YYYY-MM`, T-AP04-01).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $accounting_period
 * @property string $status
 * @property string|null $locked_by
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property string|null $lock_reason
 */
final class AccountingPeriodLock extends Model
{
    use HasUlids;

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_LOCKED = 'LOCKED';

    protected $connection = 'tenant_shared';

    protected $table = 'accounting_period_locks';

    protected $fillable = [
        'tenant_id',
        'accounting_period',
        'status',
        'locked_by',
        'locked_at',
        'lock_reason',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
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

