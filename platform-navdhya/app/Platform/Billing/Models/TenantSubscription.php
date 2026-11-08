<?php

namespace App\Platform\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant SaaS Subscription (Phase 6, Task 6.1.2).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $plan_id
 * @property string $status
 * @property \Illuminate\Support\Carbon $current_period_start
 * @property \Illuminate\Support\Carbon $current_period_end
 * @property bool $cancel_at_period_end
 * @property string|null $provider_subscription_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TenantSubscription extends Model
{
    use HasUlids;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_TRIALING = 'TRIALING';
    public const STATUS_PAST_DUE = 'PAST_DUE';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $connection = 'central';

    protected $table = 'tenant_subscriptions';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'provider_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PlatformSubscriptionPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlatformSubscriptionPlan::class, 'plan_id');
    }
}

