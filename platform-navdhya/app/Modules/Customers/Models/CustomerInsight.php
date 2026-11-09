<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Devotee aggregated intelligence (E1 / E5).
 *
 * @property string $tenant_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $last_active_at
 * @property int|null $booking_cadence_days
 * @property array<string, mixed>|null $category_affinity
 * @property int $reminder_engagement_rate
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class CustomerInsight extends Model
{
    use BelongsToTenant;

    public const CREATED_AT = null;

    protected $connection = 'tenant_shared';

    protected $table = 'customer_insights';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'last_active_at',
        'booking_cadence_days',
        'category_affinity',
        'reminder_engagement_rate',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'last_active_at' => 'datetime',
            'booking_cadence_days' => 'integer',
            'category_affinity' => 'array',
            'reminder_engagement_rate' => 'integer',
            'updated_at' => 'datetime',
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
