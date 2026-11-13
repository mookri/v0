<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SupportRefundRequest extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATE_REQUESTED = 'REQUESTED';
    public const STATE_APPROVED = 'APPROVED';
    public const STATE_EXECUTED = 'EXECUTED';
    public const STATE_REJECTED = 'REJECTED';

    protected $connection = 'tenant_shared';
    protected $table = 'support_refund_requests';

    protected $fillable = [
        'tenant_id', 'case_id', 'booking_id', 'amount_minor', 'currency', 'basis',
        'proposed_type', 'remedy_instrument', 'devotee_freely_consented_at',
        'requested_by_id', 'approved_by_id', 'finance_execution_reference',
        'executed_at', 'state', 'sla_due_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'devotee_freely_consented_at' => 'datetime',
            'executed_at' => 'datetime',
            'sla_due_at' => 'datetime',
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

