<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VerificationAuthorityGrant extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'verification_authority_grants';

    protected $guarded = [];

    protected $casts = [
        'resolution_date' => 'date',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}

