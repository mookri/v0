<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\BookingTransitionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Booking transition row (tenant_shared, append-only).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $booking_id
 * @property string $from_status
 * @property string $to_status
 * @property string|null $reason
 * @property string $actor_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class BookingTransition extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<BookingTransitionFactory> */
    use HasFactory;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'booking_id', 'from_status', 'to_status', 'reason', 'actor_id',
    ];

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
     * @return Factory<BookingTransition>
     */
    protected static function newFactory(): Factory
    {
        return BookingTransitionFactory::new();
    }
}
