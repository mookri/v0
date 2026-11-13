<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SupportCase extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'support_cases';

    protected $fillable = [
        'tenant_id',
        'case_reference',
        'reporter_user_id',
        'subject',
        'case_type',
        'category',
        'status',
        'urgency',
        'visibility_scope',
        'subject_type',
        'subject_id',
        'booking_id',
        'payment_id',
        'incident_id',
        'review_id',
        'legacy_ticket_id',
        'intake_channel',
        'acknowledged_at',
        'due_at',
        'sla_policy_version',
        'assignee_id',
        'assigned_team',
        'reopen_count',
        'resolved_at',
        'closed_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'reporter_user_id' => 'integer',
            'reopen_count' => 'integer',
            'version' => 'integer',
            'acknowledged_at' => 'datetime',
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
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

