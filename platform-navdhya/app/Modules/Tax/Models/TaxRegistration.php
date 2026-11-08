<?php

namespace App\Modules\Tax\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Tax Registration (Phase 3, Task 3.2.1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $jurisdiction
 * @property string $tax_type
 * @property string $registration_number
 * @property string|null $state_province
 * @property bool $is_headquarters
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TaxRegistration extends Model
{
    use HasUlids;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_EXPIRED = 'EXPIRED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'jurisdiction',
        'tax_type',
        'registration_number',
        'state_province',
        'is_headquarters',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_headquarters' => 'boolean',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
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

