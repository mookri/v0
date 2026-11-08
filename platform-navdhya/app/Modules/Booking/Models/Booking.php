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
 * @property string|null $idempotency_key
 * @property string|null $idempotency_hash
 * @property int|null $idempotency_hash_version
 * @property string|null $service_sku
 * @property string $status
 * @property \Carbon\CarbonImmutable|\Illuminate\Support\Carbon|null $scheduled_at
 * @property int $duration_minutes
 * @property array<string, mixed>|null $address_snapshot
 * @property int|null $total_minor
 * @property string|null $currency
 * @property int $version
 * @property string|null $current_revision_id
 * @property \Carbon\CarbonImmutable|\Illuminate\Support\Carbon|null $checkout_expires_at
 * @property \Carbon\CarbonImmutable|\Illuminate\Support\Carbon|null $service_start_at
 * @property \Carbon\CarbonImmutable|\Illuminate\Support\Carbon|null $service_end_at
 * @property string|null $service_timezone
 * @property string|null $rebooked_from_id
 * @property string|null $accepted_quote_hash
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
        'tenant_id', 'user_id', 'service_sku', 'idempotency_key', 'idempotency_hash', 'idempotency_hash_version',
        'status', 'scheduled_at',
        'duration_minutes', 'address_snapshot', 'total_minor', 'currency',
        'version',
        'current_revision_id', 'checkout_expires_at', 'service_start_at', 'service_end_at',
        'service_timezone', 'rebooked_from_id', 'accepted_quote_hash',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'checkout_expires_at' => 'datetime',
            'service_start_at' => 'datetime',
            'service_end_at' => 'datetime',
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<BookingRevision, $this>
     */
    public function revisions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookingRevision::class, 'booking_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ResourceAllocation, $this>
     */
    public function allocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ResourceAllocation::class, 'booking_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<BookingChange, $this>
     */
    public function changes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookingChange::class, 'booking_id');
    }
}
