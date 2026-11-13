<?php

declare(strict_types=1);

namespace App\Modules\Incident\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class IncidentSeverityHistory extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public $timestamps = false;

    protected $connection = 'tenant_shared';
    protected $table = 'incident_severity_history';

    protected $fillable = [
        'tenant_id', 'incident_id', 'previous_severity', 'new_severity',
        'actor_id', 'reason', 'is_downgrade', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'is_downgrade' => 'boolean',
            'occurred_at' => 'datetime',
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

