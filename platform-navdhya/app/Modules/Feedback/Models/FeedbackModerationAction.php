<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackModerationAction extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public $timestamps = false;

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_moderation_actions';

    protected $fillable = [
        'tenant_id', 'submission_id', 'action', 'actor_id', 'reason_code',
        'notes', 'policy_version', 'appealable', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'appealable' => 'boolean',
            'occurred_at' => 'datetime',
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

