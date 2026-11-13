<?php

declare(strict_types=1);

namespace App\Modules\Incident\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class IncidentTimeline extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public $timestamps = false;

    protected $connection = 'tenant_shared';
    protected $table = 'incident_timeline';

    protected $fillable = [
        'tenant_id', 'incident_id', 'entry_type', 'actor_id', 'summary',
        'metadata_json', 'occurred_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'occurred_at' => 'datetime',
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

