<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\CustomerSettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer setting row (tenant_shared): one per (tenant, user, key) over
 * the closed v1 key set. Keys outside KEYS are rejected at the
 * controller (422) — the table never stores undeclared preferences.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $key
 * @property mixed $value
 */
final class CustomerSetting extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<CustomerSettingFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed> key => default value
     */
    public const KEYS = [
        'locale' => 'en',
        'notifications_email' => true,
        'notifications_sms' => true,
        'marketing_opt_in' => false,
    ];

    protected $connection = 'tenant_shared';

    protected $fillable = ['tenant_id', 'user_id', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
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
     * @return Factory<CustomerSetting>
     */
    protected static function newFactory(): Factory
    {
        return CustomerSettingFactory::new();
    }
}
