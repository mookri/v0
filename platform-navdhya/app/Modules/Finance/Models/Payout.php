<?php

namespace App\Modules\Finance\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Payout with Maker/Checker Dual Custody (Phase 5, Task 5.1.7).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $payout_batch_id
 * @property string $beneficiary_id
 * @property int $amount_minor
 * @property string $currency
 * @property string $status
 * @property string $maker_id
 * @property string|null $checker_id
 * @property \Carbon\CarbonImmutable|\Illuminate\Support\Carbon|null $approved_at
 * @property string|null $payout_provider
 * @property string|null $payout_reference
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Payout extends Model
{
    use HasUlids;

    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_SUCCEEDED = 'SUCCEEDED';
    public const STATUS_FAILED = 'FAILED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'payout_batch_id',
        'beneficiary_id',
        'amount_minor',
        'currency',
        'status',
        'maker_id',
        'checker_id',
        'approved_at',
        'payout_provider',
        'payout_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'approved_at' => 'datetime',
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
