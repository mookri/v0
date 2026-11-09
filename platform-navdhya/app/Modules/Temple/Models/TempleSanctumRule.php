<?php

namespace App\Modules\Temple\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temple Sanctum Rule model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $temple_id
 * @property string|null $deity_id
 * @property int $max_occupancy_units
 * @property array<string, mixed>|null $entry_constraints
 * @property bool $allows_multiple_events
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TempleSanctumRule extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'temple_sanctum_rules';

    protected $fillable = [
        'tenant_id',
        'temple_id',
        'deity_id',
        'max_occupancy_units',
        'entry_constraints',
        'allows_multiple_events',
    ];

    protected function casts(): array
    {
        return [
            'max_occupancy_units' => 'integer',
            'entry_constraints' => 'array',
            'allows_multiple_events' => 'boolean',
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
     * @return BelongsTo<Temple, $this>
     */
    public function temple(): BelongsTo
    {
        return $this->belongsTo(Temple::class, 'temple_id', 'id');
    }
}
