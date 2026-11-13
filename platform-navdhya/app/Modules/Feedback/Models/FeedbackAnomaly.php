<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackAnomaly extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATE_HELD = 'HELD';
    public const STATE_CLEARED = 'CLEARED';
    public const STATE_ESCALATED = 'ESCALATED';

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_anomalies';

    protected $fillable = [
        'tenant_id', 'submission_id', 'rule_id', 'signal_payload',
        'state', 'reviewer_id', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'signal_payload' => 'array',
            'resolved_at' => 'datetime',
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

