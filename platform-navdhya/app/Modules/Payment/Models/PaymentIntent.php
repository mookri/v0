<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment intent row (tenant_shared, B3): the authoritative amount
 * SNAPSHOT for one booking — created once at :confirm from the
 * QuoteCalculator quote, never re-priced afterwards.
 *
 * Deliberately NOT BelongsToTenant: the webhook resolves the tenant FROM
 * this row (no tenant middleware on that route), so the global scope
 * would hide the very rows the system path must read. Connection is the
 * fixed tenant_shared (same as the outbox — the pre-promotion source of
 * truth, see TenantPromotion).
 *
 * Statuses: PENDING → SUCCEEDED | FAILED | REFUNDED. Transitions are
 * webhook-driven and guarded by status (replay-safe by construction).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $booking_id
 * @property int $amount_minor
 * @property string $currency
 * @property string $status
 * @property string $provider
 * @property bool $test_mode
 * @property string $provider_order_id
 * @property string|null $provider_payment_id
 * @property array<string, mixed>|null $quote_snapshot
 */
final class PaymentIntent extends Model
{
    use HasUlids;
    /** @use HasFactory<\Database\Factories\PaymentIntentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_SUCCEEDED = 'SUCCEEDED';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_REFUNDED = 'REFUNDED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'user_id', 'booking_id', 'amount_minor', 'currency',
        'status', 'provider', 'test_mode', 'provider_order_id',
        'provider_payment_id', 'quote_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'test_mode' => 'boolean',
            'quote_snapshot' => 'array',
        ];
    }

    /**
     * @return Factory<PaymentIntent>
     */
    protected static function newFactory(): Factory
    {
        return \Database\Factories\PaymentIntentFactory::new();
    }
}
