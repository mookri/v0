<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VerificationBackgroundCheck extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'verification_background_checks';

    protected $guarded = [];

    protected $casts = [
        'searched_at' => 'datetime',
        'responded_at' => 'datetime',
        'human_relevance_assessed' => 'boolean',
        'action_required' => 'boolean',
    ];
}

