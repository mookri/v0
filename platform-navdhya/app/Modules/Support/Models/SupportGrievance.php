<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SupportGrievance extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'support_grievances';

    protected $fillable = [
        'tenant_id', 'case_id', 'formal_flag', 'complaint_record_id',
        'verbatim_complaint', 'complaint_copy_storage_ref', 'copy_issued_at',
        'statutory_ack_due_at', 'statutory_redress_due_at', 'officer_id',
        'officer_decision', 'policy_version', 'breached_ack_at', 'breached_redress_at',
    ];

    protected function casts(): array
    {
        return [
            'formal_flag' => 'boolean',
            'copy_issued_at' => 'datetime',
            'statutory_ack_due_at' => 'datetime',
            'statutory_redress_due_at' => 'datetime',
            'breached_ack_at' => 'datetime',
            'breached_redress_at' => 'datetime',
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

