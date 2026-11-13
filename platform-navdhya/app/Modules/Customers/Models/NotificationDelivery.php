<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Communication delivery row (tenant_shared, E-CM-01): the per-channel claim
 * log behind all channel dispatchers (WhatsApp, SMS, Email, In-App).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $intent_id
 * @property int $user_id
 * @property string $kind
 * @property string $category
 * @property string $channel
 * @property string|null $provider
 * @property string|null $template_version_id
 * @property string $dedup_key
 * @property string|null $idempotency_key
 * @property string $status
 * @property int $attempt_count
 * @property \Carbon\CarbonInterface|null $submitted_at
 * @property \Carbon\CarbonInterface|null $accepted_at
 * @property \Carbon\CarbonInterface|null $sent_at
 * @property \Carbon\CarbonInterface|null $delivered_at
 * @property \Carbon\CarbonInterface|null $read_at
 * @property \Carbon\CarbonInterface|null $failed_at
 * @property \Carbon\CarbonInterface|null $reconciled_at
 * @property int|null $unit_cost_cents
 * @property string|null $error_code
 * @property string|null $recipient_reference_hash
 * @property string|null $provider_message_id
 * @property string|null $error
 */
final class NotificationDelivery extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATUS_CREATED = 'CREATED';
    public const STATUS_POLICY_CHECK = 'POLICY_CHECK';
    public const STATUS_SUPPRESSED = 'SUPPRESSED';
    public const STATUS_POLICY_BLOCKED = 'POLICY_BLOCKED';
    public const STATUS_QUEUED = 'QUEUED';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_PROVIDER_ACCEPTED = 'PROVIDER_ACCEPTED'; // 17 chars
    public const STATUS_SENT = 'SENT';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_READ = 'READ';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_DELIVERY_UNKNOWN = 'DELIVERY_UNKNOWN'; // 16 chars
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_CANCELLED = 'CANCELLED';

    // Legacy lowercase aliases for backward compatibility with early tests
    public const STATUS_PENDING = 'pending';

    protected $connection = 'tenant_shared';

    protected $table = 'communication_deliveries';

    protected $fillable = [
        'tenant_id',
        'intent_id',
        'user_id',
        'kind',
        'category',
        'channel',
        'provider',
        'template_version_id',
        'dedup_key',
        'idempotency_key',
        'status',
        'attempt_count',
        'submitted_at',
        'accepted_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'reconciled_at',
        'unit_cost_cents',
        'error_code',
        'recipient_reference_hash',
        'provider_message_id',
        'error',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'attempt_count' => 'integer',
        'unit_cost_cents' => 'integer',
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
