<?php

namespace App\Modules\Vendor\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only inventory movements ledger (tenant_shared, D1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $sku
 * @property string|null $location_id
 * @property string $movement_type
 * @property int $delta_signed
 * @property int $balance_after
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string|null $actor_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class InventoryMovement extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const TYPE_ADJUST = 'ADJUST';
    public const TYPE_HOLD = 'HOLD';
    public const TYPE_RELEASE = 'RELEASE';
    public const TYPE_COMMIT = 'COMMIT';
    public const TYPE_CONSUME = 'CONSUME';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'sku',
        'location_id',
        'movement_type',
        'delta_signed',
        'balance_after',
        'reference_type',
        'reference_id',
        'actor_id',
    ];

    protected $casts = [
        'delta_signed' => 'integer',
        'balance_after' => 'integer',
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
}
