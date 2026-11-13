<?php

namespace App\Domain\Verification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VerificationJurisdiction extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'verification_jurisdictions';

    protected $guarded = [];

    protected $casts = [
        'checklist_json' => 'array',
        'manual_mode_only' => 'boolean',
    ];
}

