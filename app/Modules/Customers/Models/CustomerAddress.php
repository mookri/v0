<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer address book row (tenant_shared). Connection follows the
 * ambient tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $label
 * @property string $line1
 * @property string|null $line2
 * @property string $city
 * @property string $state
 * @property string $postal_code
 * @property array<string, mixed>|null $geo
 * @property bool $is_default
 */
final class CustomerAddress extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory;

    public const MAX_PER_CUSTOMER = 20;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'user_id', 'label', 'line1', 'line2', 'city',
        'state', 'postal_code', 'geo', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'geo' => 'array',
            'is_default' => 'boolean',
        ];
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
     * @return Factory<CustomerAddress>
     */
    protected static function newFactory(): Factory
    {
        return CustomerAddressFactory::new();
    }
}
