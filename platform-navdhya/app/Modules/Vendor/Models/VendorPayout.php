<?php

namespace App\Modules\Vendor\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Vendor payout (tenant_shared): read-only stub — one row per vendor per
 * period (unique), surfaced to partners until recording flows land.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $vendor_id
 * @property string $period
 * @property string $status
 * @property int $amount_minor
 * @property string $currency
 * @property string|null $reference
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\VendorPayoutFactory factory()
 */
final class VendorPayout extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<\Database\Factories\VendorPayoutFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'period',
        'status',
        'amount_minor',
        'currency',
        'reference',
    ];

    /**
     * @return \Database\Factories\VendorPayoutFactory
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return \Database\Factories\VendorPayoutFactory::new();
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
     * @return array{id: string, object: string, vendor_id: string, period: string, status: string, amount_minor: int, currency: string, reference: string|null, created_at: string, updated_at: string}
     */
    public static function shape(self $payout): array
    {
        return [
            'id' => (string) $payout->getKey(),
            'object' => 'vendor_payout',
            'vendor_id' => $payout->vendor_id,
            'period' => $payout->period,
            'status' => $payout->status,
            'amount_minor' => (int) $payout->amount_minor,
            'currency' => $payout->currency,
            'reference' => $payout->reference,
            'created_at' => (string) $payout->created_at,
            'updated_at' => (string) $payout->updated_at,
        ];
    }
}
