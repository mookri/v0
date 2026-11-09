<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Devotee ritual reminder (E1 / E3).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string|null $family_member_id
 * @property string $type
 * @property string $title
 * @property array<string, mixed> $anchor_rule
 * @property list<int> $advance_days
 * @property \Illuminate\Support\Carbon|null $next_occurrence_at
 * @property string|null $last_fired_period
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class CustomerRitualReminder extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const TYPE_SHRADDHA = 'SHRADDHA';
    public const TYPE_LUNAR_BIRTHDAY = 'LUNAR_BIRTHDAY';
    public const TYPE_SOLAR_BIRTHDAY = 'SOLAR_BIRTHDAY';
    public const TYPE_WEDDING_ANNIVERSARY = 'WEDDING_ANNIVERSARY';
    public const TYPE_CUSTOM_TITHI = 'CUSTOM_TITHI';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'family_member_id',
        'type',
        'title',
        'anchor_rule',
        'advance_days',
        'next_occurrence_at',
        'last_fired_period',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'anchor_rule' => 'array',
            'advance_days' => 'array',
            'next_occurrence_at' => 'datetime',
            'is_active' => 'boolean',
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
