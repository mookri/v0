<?php

namespace App\Domain\Verification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VerificationPolicyVersion extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'verification_policy_versions';

    protected $fillable = [
        'version',
        'track',
        'rules_json',
        'active_from',
        'deprecated_at',
    ];

    protected $casts = [
        'rules_json' => 'array',
        'active_from' => 'datetime',
        'deprecated_at' => 'datetime',
    ];
}
