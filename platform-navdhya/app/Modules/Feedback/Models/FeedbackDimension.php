<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackDimension extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_dimensions';

    protected $fillable = [
        'tenant_id', 'service_type', 'code', 'label', 'scale_min', 'scale_max',
        'counts_toward', 'expert_only', 'active_version', 'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'scale_min' => 'integer',
            'scale_max' => 'integer',
            'counts_toward' => 'array',
            'expert_only' => 'boolean',
            'effective_from' => 'datetime',
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

