<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Devotee notification channel & category preference matrix (E1 / E4).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $category
 * @property string $channel
 * @property bool $is_enabled
 * @property string|null $quiet_hours_start
 * @property string|null $quiet_hours_end
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class NotificationPreference extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const CATEGORY_ACCOUNT = 'ACCOUNT';
    public const CATEGORY_BOOKING = 'BOOKING';
    public const CATEGORY_REMINDER = 'REMINDER';
    public const CATEGORY_MARKETING = 'MARKETING';

    public const CHANNEL_IN_APP = 'IN_APP';
    public const CHANNEL_SMS = 'SMS';
    public const CHANNEL_WHATSAPP = 'WHATSAPP';
    public const CHANNEL_EMAIL = 'EMAIL';

    protected $connection = 'tenant_shared';

    protected $table = 'notification_preferences';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'category',
        'channel',
        'is_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_enabled' => 'boolean',
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
