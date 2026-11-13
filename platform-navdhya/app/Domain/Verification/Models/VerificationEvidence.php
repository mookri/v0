<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VerificationEvidence extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'verification_evidence';

    protected $fillable = [
        'tenant_id',
        'claim_id',
        'document_id',
        'evidence_kind',
        'source_mode',
        'source_ref',
        'sha256',
        'verified_at',
        'verified_by',
        'metadata',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];
}
