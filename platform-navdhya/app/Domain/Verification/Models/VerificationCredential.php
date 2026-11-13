<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Registered partner credential tied to closed taxonomy + issuer record (T-TR03-02).
 */
final class VerificationCredential extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'verification_credentials';

    protected $guarded = [];

    protected $casts = [
        'award_year' => 'integer',
        'subject_scope_json' => 'array',
        'revoked_at' => 'datetime',
    ];
}

