<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VerificationClaimScope extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'verification_claim_scopes';

    protected $fillable = [
        'tenant_id',
        'claim_id',
        'scope_type',
        'scope_value',
    ];
}
