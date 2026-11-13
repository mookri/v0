<?php

declare(strict_types=1);

namespace App\Modules\Incident\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Incident extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'incidents';

    protected $fillable = [
        'tenant_id', 'incident_reference', 'type', 'severity', 'status',
        'attributed_cause', 'title', 'summary', 'commander_id', 'safety_lead_id',
        'safety_restricted', 'correlation_id', 'detected_at', 'contained_at',
        'resolved_at', 'closed_at', 'postmortem_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'safety_restricted' => 'boolean',
            'detected_at' => 'datetime',
            'contained_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'postmortem_completed_at' => 'datetime',
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

