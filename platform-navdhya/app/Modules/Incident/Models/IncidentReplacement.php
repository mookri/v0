<?php

declare(strict_types=1);

namespace App\Modules\Incident\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class IncidentReplacement extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'incident_replacements';

    protected $fillable = [
        'tenant_id', 'incident_id', 'booking_id', 'original_pandit_id', 'candidate_pandit_id',
        'score', 'eligibility_gates_passed', 'gate_disclosure_status', 'status',
        'offer_expires_at', 'devotee_consented_at', 'confirmed_by_id', 'fallback_action',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'eligibility_gates_passed' => 'array',
            'offer_expires_at' => 'datetime',
            'devotee_consented_at' => 'datetime',
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

