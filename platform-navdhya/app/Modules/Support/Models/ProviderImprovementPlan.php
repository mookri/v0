<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ProviderImprovementPlan extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATE_PROPOSED = 'PROPOSED';
    public const STATE_ACKNOWLEDGED = 'ACKNOWLEDGED';
    public const STATE_COMPLETED = 'COMPLETED';

    protected $connection = 'tenant_shared';
    protected $table = 'provider_improvement_plans';

    protected $fillable = [
        'tenant_id', 'subject_type', 'subject_id', 'trigger_pattern',
        'excluded_external_causes_count', 'evidence_incident_refs', 'actions',
        'provider_acknowledged_at', 'provider_notes', 'evaluation_window_end',
        'outcome_metrics', 'state',
    ];

    protected function casts(): array
    {
        return [
            'excluded_external_causes_count' => 'integer',
            'evidence_incident_refs' => 'array',
            'actions' => 'array',
            'provider_acknowledged_at' => 'datetime',
            'evaluation_window_end' => 'datetime',
            'outcome_metrics' => 'array',
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

