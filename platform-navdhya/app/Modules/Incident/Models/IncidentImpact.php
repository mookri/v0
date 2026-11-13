<?php

declare(strict_types=1);

namespace App\Modules\Incident\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class IncidentImpact extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'incident_impacts';

    protected $fillable = [
        'tenant_id', 'incident_id', 'booking_id', 'subject_type',
        'subject_id', 'attributed_cause', 'devotee_outcome', 'notes',
    ];

    public function getConnectionName(): ?string
    {
        try {
            return app(\App\Platform\Tenancy\TenantContext::class)->connectionName() ?? $this->connection;
        } catch (\Throwable) {
            return $this->connection;
        }
    }
}

