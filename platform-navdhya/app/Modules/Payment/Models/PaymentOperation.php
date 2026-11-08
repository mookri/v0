<?php

namespace App\Modules\Payment\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Durable Payment Operation (Phase 4, Task 4.1.3).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $booking_id
 * @property string $operation_type
 * @property int $amount_minor
 * @property string $currency
 * @property string $status
 * @property string $provider_code
 * @property string|null $provider_operation_id
 * @property string|null $error_code
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Carbon\CarbonImmutable|\Illuminate\Support\Carbon|null $next_retry_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PaymentOperation extends Model
{
    use HasUlids;

    public const TYPE_AUTHORIZE = 'AUTHORIZE';
    public const TYPE_CAPTURE = 'CAPTURE';
    public const TYPE_REFUND = 'REFUND';
    public const TYPE_VOID = 'VOID';
    public const TYPE_DISPUTE = 'DISPUTE';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_SUCCEEDED = 'SUCCEEDED';
    public const STATUS_FAILED = 'FAILED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'operation_type',
        'amount_minor',
        'currency',
        'status',
        'provider_code',
        'provider_operation_id',
        'error_code',
        'error_message',
        'retry_count',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'retry_count' => 'integer',
            'next_retry_at' => 'datetime',
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
     * @return HasMany<PaymentAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class, 'payment_operation_id');
    }
}
