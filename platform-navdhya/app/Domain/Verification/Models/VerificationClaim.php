<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class VerificationClaim extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'verification_claims';

    protected $fillable = [
        'tenant_id',
        'subject_type',
        'subject_id',
        'application_id',
        'claim_kind',
        'mode',
        'confidence',
        'status',
        'policy_version',
        'legacy_asserted',
        'reason_code',
        'note',
        'decided_by',
        'countersigned_by',
        'evidence_set_hash',
        'decided_at',
        'expires_at',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'metadata',
    ];

    protected $casts = [
        'legacy_asserted' => 'boolean',
        'decided_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopes(): HasMany
    {
        return $this->hasMany(VerificationClaimScope::class, 'claim_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(VerificationEvidence::class, 'claim_id');
    }
}
