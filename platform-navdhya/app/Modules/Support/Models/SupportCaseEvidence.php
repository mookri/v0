<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SupportCaseEvidence extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'support_case_evidence';

    protected $fillable = [
        'tenant_id', 'case_id', 'evidence_reference', 'description', 'purpose',
        'provenance', 'storage_reference', 'sensitivity_class', 'retention_class',
        'uploaded_by_id', 'retained_until',
    ];

    protected function casts(): array
    {
        return [
            'retained_until' => 'datetime',
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

