<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackInvitation extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATE_PENDING = 'PENDING';
    public const STATE_SENT = 'SENT';
    public const STATE_SUBMITTED = 'SUBMITTED';
    public const STATE_EXPIRED = 'EXPIRED';
    public const STATE_SUPPRESSED_BY_SAFETY = 'SUPPRESSED_BY_SAFETY';

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_invitations';

    protected $fillable = [
        'tenant_id', 'booking_id', 'invitee_user_id', 'subject_type', 'subject_id',
        'window_opens_at', 'window_closes_at', 'state', 'channel', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'invitee_user_id' => 'integer',
            'window_opens_at' => 'datetime',
            'window_closes_at' => 'datetime',
            'sent_at' => 'datetime',
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

