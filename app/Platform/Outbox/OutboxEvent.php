<?php

namespace App\Platform\Outbox;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Transactional outbox row (tenant_shared, F2). Written only through
 * Outbox::append inside the producer's transaction; claimed and marked
 * by `outbox:drain` under a row lock so each event reaches its handler
 * exactly once per successful claim.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property int $attempts
 * @property string|null $last_error
 * @property \Illuminate\Support\Carbon|null $dispatched_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class OutboxEvent extends Model
{
    use BelongsToTenant;
    use HasUlids;

    /** Retry cap: rows at the cap stay pending for ops, never re-claimed. */
    public const MAX_ATTEMPTS = 5;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'aggregate_type', 'aggregate_id', 'event_type',
        'payload', 'attempts', 'last_error', 'dispatched_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'dispatched_at' => 'datetime',
        ];
    }

    public function getConnectionName(): ?string
    {
        try {
            $context = app(\App\Platform\Tenancy\TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }

    /**
     * Drain claim scope: never dispatched and under the retry cap.
     *
     * @param  Builder<OutboxEvent>  $query
     * @return Builder<OutboxEvent>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('dispatched_at')->where('attempts', '<', self::MAX_ATTEMPTS);
    }
}
