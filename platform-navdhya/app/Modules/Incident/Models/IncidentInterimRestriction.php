<?php

declare(strict_types=1);

namespace App\Modules\Incident\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class IncidentInterimRestriction extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'incident_interim_restrictions';

    protected $fillable = [
        'tenant_id', 'incident_id', 'support_case_id', 'subject_type', 'subject_id',
        'restriction_kind', 'reason', 'proportionality_note', 'requested_by_id',
        'approved_by_safety_lead_id', 'expires_at', 'lifted_at', 'lifted_by_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'lifted_at' => 'datetime',
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

