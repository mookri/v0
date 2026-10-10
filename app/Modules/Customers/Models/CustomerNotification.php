<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\CustomerNotificationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer notification row (tenant_shared): written by the N1
 * dispatcher (routing/dedup/quiet-hours policy in NotificationChannels),
 * read + marked read here. category = communication class (routing
 * matrix), dedup_key = spec identity rule:event:recipient:channel
 * (unique per tenant — replays collapse).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $kind
 * @property string $category
 * @property string $title
 * @property string|null $body
 * @property string|null $dedup_key
 * @property \Illuminate\Support\Carbon|null $read_at
 */
final class CustomerNotification extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<CustomerNotificationFactory> */
    use HasFactory;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'user_id', 'kind', 'category', 'title', 'body', 'dedup_key', 'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
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
     * @return Factory<CustomerNotification>
     */
    protected static function newFactory(): Factory
    {
        return CustomerNotificationFactory::new();
    }
}
