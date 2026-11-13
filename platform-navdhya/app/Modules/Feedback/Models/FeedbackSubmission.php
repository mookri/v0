<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeedbackSubmission extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';
    protected $table = 'feedback_submissions';

    protected $fillable = [
        'tenant_id', 'booking_id', 'review_reference', 'reviewer_user_id',
        'subject_type', 'subject_id', 'service_instance_id', 'booking_revision_id',
        'service_category', 'overall_rating', 'original_text', 'share_publicly',
        'edit_history', 'submitted_at', 'edit_window_closes_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewer_user_id' => 'integer',
            'overall_rating' => 'integer',
            'share_publicly' => 'boolean',
            'edit_history' => 'array',
            'submitted_at' => 'datetime',
            'edit_window_closes_at' => 'datetime',
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

