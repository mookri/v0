<?php

namespace App\Modules\Payment\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment Operation Attempt Audit (Phase 4, Task 4.1.4).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $payment_operation_id
 * @property int $attempt_number
 * @property string $status
 * @property array<string, mixed>|null $provider_payload_json
 * @property array<string, mixed>|null $provider_response_json
 * @property int|null $duration_ms
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PaymentAttempt extends Model
{
    use HasUlids;

    public const STATUS_INITIATED = 'INITIATED';
    public const STATUS_SUCCEEDED = 'SUCCEEDED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_TIMED_OUT = 'TIMED_OUT';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'payment_operation_id',
        'attempt_number',
        'status',
        'provider_payload_json',
        'provider_response_json',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'duration_ms' => 'integer',
            'provider_payload_json' => 'array',
            'provider_response_json' => 'array',
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
