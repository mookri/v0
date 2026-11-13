<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SupportCaseResolution extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'support_case_resolutions';

    protected $fillable = [
        'tenant_id',
        'case_id',
        'remedy_type',
        'decision_summary',
        'decided_by_id',
        'proposed_by_id',
        'accepted_by_id',
        'consent_captured_at',
        'dossier_json',
        'policy_version',
    ];

    protected function casts(): array
    {
        return [
            'consent_captured_at' => 'datetime',
            'dossier_json' => 'array',
        ];
    }

    public function getConnectionName(): ?string
    {
        try {
            $context = app(\App\Platform\Tenancy\TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }
}

