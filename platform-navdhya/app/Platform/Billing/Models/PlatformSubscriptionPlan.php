<?php

namespace App\Platform\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform Subscription Plan (Phase 6, Task 6.1.1).
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $billing_interval
 * @property int $price_minor
 * @property string $currency
 * @property array<string, mixed>|null $features_json
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PlatformSubscriptionPlan extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'platform_subscription_plans';

    protected $fillable = [
        'code',
        'name',
        'billing_interval',
        'price_minor',
        'currency',
        'features_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'features_json' => 'array',
            'is_active' => 'boolean',
        ];
    }
}

