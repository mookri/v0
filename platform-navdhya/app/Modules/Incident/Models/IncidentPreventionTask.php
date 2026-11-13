<?php

declare(strict_types=1);

namespace App\Modules\Incident\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class IncidentPreventionTask extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'incident_prevention_tasks';

    protected $fillable = [
        'tenant_id', 'incident_id', 'postmortem_id', 'title', 'task_type',
        'owner_id', 'due_at', 'status', 'linked_case_id',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
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

