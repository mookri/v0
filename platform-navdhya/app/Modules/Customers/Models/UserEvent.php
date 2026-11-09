<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * User telemetry event model (E1 / E5).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int|null $user_id
 * @property string $event_type
 * @property array<string, mixed> $properties
 * @property array<string, mixed>|null $context
 * @property string $source
 * @property string $purpose
 * @property string|null $origin_outbox_id
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property \Illuminate\Support\Carbon $created_at
 */
final class UserEvent extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const UPDATED_AT = null;

    public const SOURCE_WEB_COLLECTOR = 'WEB_COLLECTOR';
    public const SOURCE_SERVER_OUTBOX = 'SERVER_OUTBOX';
    public const SOURCE_API = 'API';

    protected $connection = 'tenant_shared';

    protected $table = 'user_events';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'event_type',
        'properties',
        'context',
        'source',
        'purpose',
        'origin_outbox_id',
        'occurred_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'properties' => 'array',
            'context' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
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
