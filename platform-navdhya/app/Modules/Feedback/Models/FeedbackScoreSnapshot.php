<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackScoreSnapshot extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public $timestamps = false;

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_score_snapshots';

    protected $fillable = [
        'tenant_id', 'subject_type', 'subject_id', 'period', 'sample_n',
        'bayesian_average', 'wilson_punctuality_lower', 'confidence_tier',
        'prior_version', 'snapshot_at',
    ];

    protected function casts(): array
    {
        return [
            'sample_n' => 'integer',
            'bayesian_average' => 'float',
            'wilson_punctuality_lower' => 'float',
            'snapshot_at' => 'datetime',
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
