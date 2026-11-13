<?php

declare(strict_types=1);

namespace App\Modules\Incident\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class IncidentCorrelation extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATUS_PROPOSED = 'PROPOSED';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_REVOKED = 'REVOKED';

    protected $connection = 'tenant_shared';
    protected $table = 'incident_correlations';

    protected $fillable = [
        'tenant_id', 'incident_id', 'candidate_booking_id', 'candidate_incident_id',
        'correlation_key', 'score', 'evidence_json', 'status',
        'confirmed_by_id', 'revoked_by_id', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'evidence_json' => 'array',
            'decided_at' => 'datetime',
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

