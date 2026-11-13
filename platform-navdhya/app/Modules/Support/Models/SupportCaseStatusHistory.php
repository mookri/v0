<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SupportCaseStatusHistory extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public $timestamps = false;

    protected $connection = 'tenant_shared';

    protected $table = 'support_case_status_history';

    protected $fillable = [
        'tenant_id',
        'case_id',
        'actor_id',
        'actor_type',
        'action',
        'previous_state',
        'new_state',
        'reason',
        'policy_version',
        'correlation_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
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

