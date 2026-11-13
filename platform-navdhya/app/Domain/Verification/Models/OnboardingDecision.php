<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class OnboardingDecision extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'onboarding_decisions';

    protected $fillable = [
        'tenant_id',
        'application_id',
        'decision',
        'status',
        'reason_code',
        'note',
        'primary_reviewer_id',
        'second_reviewer_id',
        'evidence_set_hash',
        'policy_version',
        'countersigned_at',
    ];

    protected $casts = [
        'countersigned_at' => 'datetime',
    ];
}
