<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackPublication extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const STATE_PUBLISHED = 'PUBLISHED';
    public const STATE_HELD = 'HELD';
    public const STATE_WITHHELD = 'WITHHELD';

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_publications';

    protected $fillable = [
        'tenant_id', 'submission_id', 'state', 'redacted_text', 'reason_code', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
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

