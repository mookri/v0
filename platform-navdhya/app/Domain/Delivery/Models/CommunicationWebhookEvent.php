<?php

namespace App\Domain\Delivery\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Dedup log for provider webhook callbacks (E-CM-02).
 *
 * @property string $id
 * @property string $provider
 * @property string $provider_message_id
 * @property string $event_type
 * @property \Carbon\CarbonInterface $occurred_at
 * @property string $payload_hash
 * @property \Carbon\CarbonInterface|null $processed_at
 */
final class CommunicationWebhookEvent extends Model
{
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'communication_webhook_events';

    protected $fillable = [
        'provider',
        'provider_message_id',
        'event_type',
        'occurred_at',
        'payload_hash',
        'processed_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}

