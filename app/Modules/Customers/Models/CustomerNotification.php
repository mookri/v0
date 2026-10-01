<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\CustomerNotificationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer notification row (tenant_shared): written by producers
 * (booking transitions B5+, system notices), read + marked read here.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $kind
 * @property string $title
 * @property string|null $body
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
        'tenant_id', 'user_id', 'kind', 'title', 'body', 'read_at',
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
