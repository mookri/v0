<?php

namespace App\Domain\Privacy\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PrivacyConsent extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'privacy_consents';

    protected $fillable = [
        'tenant_id',
        'subject_identity_id',
        'purpose',
        'granted',
        'policy_version',
        'ip_hash',
        'user_agent',
        'granted_at',
        'withdrawn_at',
        'expires_at',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}

