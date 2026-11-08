<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Immutable Booking Revision (Phase 2, Task 2.1.2).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $booking_id
 * @property int $version
 * @property string|null $quote_id
 * @property string|null $commercial_policy_version_id
 * @property array<string, mixed>|null $service_address_json
 * @property string $service_mode
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class BookingRevision extends Model
{
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'version',
        'quote_id',
        'commercial_policy_version_id',
        'service_address_json',
        'service_mode',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'service_address_json' => 'array',
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

    /**
     * @return HasMany<BookingLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(BookingLine::class, 'revision_id');
    }
}

