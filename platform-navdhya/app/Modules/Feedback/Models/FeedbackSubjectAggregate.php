<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackSubjectAggregate extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const PERIOD_LIFETIME = 'LIFETIME';
    public const PERIOD_RECENT_90D = 'RECENT_90D';

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_subject_aggregates';

    protected $fillable = [
        'tenant_id', 'subject_type', 'subject_id', 'period',
        'sample_n', 'mean_average', 'bayesian_average',
        'wilson_punctuality_lower', 'wilson_complaint_free_lower',
        'confidence_tier', 'prior_code', 'prior_version',
        'decay_lambda', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'sample_n' => 'integer',
            'mean_average' => 'float',
            'bayesian_average' => 'float',
            'wilson_punctuality_lower' => 'float',
            'wilson_complaint_free_lower' => 'float',
            'decay_lambda' => 'float',
            'computed_at' => 'datetime',
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

