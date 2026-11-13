<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Recorded issuer outreach lifecycle entry (T-TR03-03).
 */
final class VerificationCredentialResponse extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'verification_credential_responses';

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'responded_at' => 'datetime',
    ];
}

