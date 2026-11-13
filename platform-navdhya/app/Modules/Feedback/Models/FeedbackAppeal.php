<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackAppeal extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_UPHELD = 'UPHELD';
    public const STATUS_OVERTURNED = 'OVERTURNED';

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_appeals';

    protected $fillable = [
        'tenant_id', 'submission_id', 'appellant_id', 'appellant_role',
        'grounds', 'status', 'decider_id', 'decision_notes', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
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

