<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Notification delivery row (tenant_shared, N3): the per-channel claim
 * log behind the dispatcher's email leg. Written only by
 * DispatchCustomerNotification — pending on claim, sent after the
 * provider hand-off, suppressed when the leg can never go out (no
 * address). dedup_key is the spec identity
 * rule:event:recipient:channel, unique per tenant: replays collapse and
 * retries resume their own row instead of doubling a send.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $kind
 * @property string $category
 * @property string $channel
 * @property string|null $dedup_key
 * @property string $status
 * @property string|null $provider_message_id
 * @property string|null $error
 */
final class NotificationDelivery extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_SUPPRESSED = 'suppressed';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'user_id', 'kind', 'category', 'channel', 'dedup_key',
        'status', 'provider_message_id', 'error',
    ];

    public function getConnectionName(): ?string
    {
        try {
            $context = app(\App\Platform\Tenancy\TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }
}
