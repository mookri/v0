<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class OnboardingAppeal extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'onboarding_appeals';

    protected $guarded = [];

    protected $casts = [
        'new_evidence_ids_json' => 'array',
        'sla_due_at' => 'datetime',
        'decided_at' => 'datetime',
    ];
}

