<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Idempotent command store (Phase 1, Task 1.2.1).
 * Deduplicates commands per (tenant_id, actor_id, verb, idempotency_key).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $actor_id
 * @property string $verb
 * @property string $idempotency_key
 * @property string $canonical_hash
 * @property string|null $target_id
 * @property string $outcome_status
 * @property array<string, mixed>|null $response_payload
 * @property int|null $http_status
 * @property \Illuminate\Support\Carbon|null $expires_at
 */
final class BookingCommand extends Model
{
    use HasUlids;

    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_SUCCEEDED = 'SUCCEEDED';
    public const STATUS_FAILED = 'FAILED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'actor_id',
        'verb',
        'idempotency_key',
        'canonical_hash',
        'target_id',
        'outcome_status',
        'response_payload',
        'http_status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_payload' => 'array',
            'http_status' => 'integer',
            'expires_at' => 'datetime',
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
