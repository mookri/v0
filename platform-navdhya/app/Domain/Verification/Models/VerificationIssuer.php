<?php

namespace App\Domain\Verification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Evidence-graded institutional issuer directory (central DB, T-TR03-01).
 */
final class VerificationIssuer extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'verification_issuers';

    protected $guarded = [];

    protected $casts = [
        'established_year' => 'integer',
        'contact_verified_at' => 'datetime',
        'response_rate' => 'float',
        'last_confirmed_at' => 'datetime',
    ];
}

