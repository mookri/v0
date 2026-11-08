<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Line item in a booking revision (Phase 2, Task 2.1.2).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $revision_id
 * @property int $line_number
 * @property string $line_kind
 * @property string $sku
 * @property string|null $variant_id
 * @property int $quantity
 * @property int $server_duration_minutes
 * @property string|null $selected_pandit_id
 * @property string|null $selected_temple_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class BookingLine extends Model
{
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'revision_id',
        'line_number',
        'line_kind',
        'sku',
        'variant_id',
        'quantity',
        'server_duration_minutes',
        'selected_pandit_id',
        'selected_temple_id',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'integer',
            'server_duration_minutes' => 'integer',
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

