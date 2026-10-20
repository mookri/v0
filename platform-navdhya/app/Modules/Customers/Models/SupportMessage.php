<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\SupportMessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Support message row (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $ticket_id
 * @property int $user_id
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class SupportMessage extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<SupportMessageFactory> */
    use HasFactory;

    protected $connection = 'tenant_shared';

    protected $fillable = ['tenant_id', 'ticket_id', 'user_id', 'body'];

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
     * @return Factory<SupportMessage>
     */
    protected static function newFactory(): Factory
    {
        return SupportMessageFactory::new();
    }
}
