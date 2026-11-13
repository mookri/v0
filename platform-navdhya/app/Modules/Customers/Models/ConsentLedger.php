<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only consent ledger (E1 / E4 / E6).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $purpose
 * @property string $state
 * @property string $source
 * @property string $policy_version
 * @property string|null $ip_hash
 * @property string|null $user_agent_hash
 * @property \Illuminate\Support\Carbon $created_at
 */
final class ConsentLedger extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const UPDATED_AT = null;

    public const PURPOSE_TRANSACTIONAL = 'TRANSACTIONAL';
    public const PURPOSE_RITUAL_REMINDERS = 'RITUAL_REMINDERS';
    public const PURPOSE_ANALYTICS = 'ANALYTICS';
    public const PURPOSE_MARKETING = 'MARKETING';
    public const PURPOSE_AUTHENTICATION = 'AUTHENTICATION';
    public const PURPOSE_BOOKING_UPDATES = 'BOOKING_UPDATES';
    public const PURPOSE_SERVICE_UPDATES = 'SERVICE_UPDATES';
    public const PURPOSE_DIGITAL_DELIVERY = 'DIGITAL_DELIVERY';
    public const PURPOSE_FINANCIAL_COMMUNICATIONS = 'FINANCIAL_COMMUNICATIONS';
    public const PURPOSE_FESTIVAL_UPDATES = 'FESTIVAL_UPDATES';

    public const STATE_GRANTED = 'GRANTED';
    public const STATE_WITHDRAWN = 'WITHDRAWN';
    public const STATE_EXPIRED = 'EXPIRED';

    protected $connection = 'tenant_shared';

    protected $table = 'consent_ledger';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'purpose',
        'state',
        'source',
        'policy_version',
        'ip_hash',
        'user_agent_hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
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
