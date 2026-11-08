<?php

namespace App\Modules\Tax\Models;

use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Commercial Policy Version (Phase 3, Task 3.1.2).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $version
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property array<string, mixed> $policy_json
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class CommercialPolicyVersion extends Model
{
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'version',
        'effective_from',
        'effective_to',
        'policy_json',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'policy_json' => 'array',
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

