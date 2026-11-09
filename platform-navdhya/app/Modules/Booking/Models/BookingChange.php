<?php

namespace App\Modules\Booking\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Reschedule and Cancellation Proposals (Phase 2, Task 2.4.1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $booking_id
 * @property string $kind
 * @property string|null $proposed_revision_id
 * @property string|null $old_quote_id
 * @property string|null $new_quote_id
 * @property int $price_difference_minor
 * @property int $fee_minor
 * @property int $refund_minor
 * @property string $status
 * @property string $requested_by
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class BookingChange extends Model
{
    use HasUlids;

    public const KIND_RESCHEDULE = 'RESCHEDULE';
    public const KIND_CANCELLATION = 'CANCELLATION';

    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_APPLIED = 'APPLIED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_EXPIRED = 'EXPIRED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'kind',
        'proposed_revision_id',
        'old_quote_id',
        'new_quote_id',
        'price_difference_minor',
        'fee_minor',
        'refund_minor',
        'status',
        'requested_by',
        'approved_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'price_difference_minor' => 'integer',
            'fee_minor' => 'integer',
            'refund_minor' => 'integer',
            'expires_at' => 'datetime',
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
