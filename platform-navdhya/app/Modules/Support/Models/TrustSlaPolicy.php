<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class TrustSlaPolicy extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const APPROVAL_PENDING = 'PENDING_COUNSEL_APPROVAL';
    public const APPROVAL_APPROVED = 'OFFICER_APPROVED';

    protected $connection = 'tenant_shared';
    protected $table = 'trust_sla_policies';

    protected $fillable = [
        'tenant_id', 'scope', 'metric', 'duration_minutes',
        'calendar_handling', 'effective_from', 'effective_until',
        'approved_by_officer_id', 'approved_at', 'approval_status', 'version',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function getConnectionName(): ?string
    {
        try {
            return app(\App\Platform\Tenancy\TenantContext::class)->connectionName() ?? $this->connection;
        } catch (\Throwable) {
            return $this->connection;
        }
    }
}

