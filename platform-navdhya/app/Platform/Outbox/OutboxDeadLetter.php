<?php

namespace App\Platform\Outbox;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Model representing an event quarantined in the Dead Letter Queue (DLQ).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $original_event_id
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property int $attempts
 * @property string|null $last_error
 * @property \Carbon\CarbonImmutable|null $quarantined_at
 * @property \Carbon\CarbonImmutable|null $replayed_at
 */
final class OutboxDeadLetter extends Model
{
    use HasUlids;

    protected $table = 'outbox_dead_letters';

    protected $connection = 'tenant_shared';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'quarantined_at' => 'immutable_datetime',
            'replayed_at' => 'immutable_datetime',
        ];
    }
}

