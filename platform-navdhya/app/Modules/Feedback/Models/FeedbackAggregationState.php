<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackAggregationState extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_aggregation_state';

    protected $fillable = [
        'tenant_id', 'submission_id', 'counts_toward', 'excluded_by_cause', 'cause_code',
    ];

    protected function casts(): array
    {
        return [
            'counts_toward' => 'array',
            'excluded_by_cause' => 'boolean',
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

