<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Booking transaction row (tenant_shared, ADR-005). Status is mutated
 * only through BookingLifecycle-checked controller verbs. Connection
 * follows the ambient tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string|null $service_sku
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property int $duration_minutes
 * @property array<string, mixed>|null $address_snapshot
 * @property int $version
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Booking extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'user_id', 'service_sku', 'status', 'scheduled_at',
        'duration_minutes', 'address_snapshot', 'version',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'address_snapshot' => 'array',
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
     * @return Factory<Booking>
     */
    protected static function newFactory(): Factory
    {
        return BookingFactory::new();
    }
}
