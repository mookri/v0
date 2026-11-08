<?php

namespace App\Modules\Tax\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Commercial Profile for Tenant (Phase 3, Task 3.1.1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $selling_model
 * @property string $default_currency
 * @property int $commission_rate_bps
 * @property int $platform_fee_minor
 * @property bool $pass_through_payment_fees
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class CommercialProfile extends Model
{
    use HasUlids;

    public const MODEL_PRINCIPAL = 'PRINCIPAL';
    public const MODEL_AGENT = 'AGENT';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'selling_model',
        'default_currency',
        'commission_rate_bps',
        'platform_fee_minor',
        'pass_through_payment_fees',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate_bps' => 'integer',
            'platform_fee_minor' => 'integer',
            'pass_through_payment_fees' => 'boolean',
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

