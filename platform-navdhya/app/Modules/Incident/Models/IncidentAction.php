<?php

declare(strict_types=1);

namespace App\Modules\Incident\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class IncidentAction extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public $timestamps = false;

    protected $connection = 'tenant_shared';
    protected $table = 'incident_actions';

    protected $fillable = [
        'tenant_id', 'incident_id', 'action_type', 'actor_id', 'notes', 'executed_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
            'created_at' => 'datetime',
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

