<?php

namespace App\Domain\Delivery\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Transactional notification outbox model (E-CM-03).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $event_id
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property string $event_type
 * @property array<string, mixed> $payload_json
 * @property string $status
 * @property int $attempt_count
 * @property \Carbon\CarbonInterface|null $next_attempt_at
 */
final class NotificationOutbox extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_FAILED = 'failed';

    protected $connection = 'tenant_shared';

    protected $table = 'notification_outbox';

    protected $fillable = [
        'tenant_id',
        'event_id',
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'payload_json',
        'status',
        'attempt_count',
        'next_attempt_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'attempt_count' => 'integer',
        'next_attempt_at' => 'datetime',
    ];
}

