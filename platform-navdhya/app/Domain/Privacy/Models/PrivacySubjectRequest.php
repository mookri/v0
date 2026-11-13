<?php

namespace App\Domain\Privacy\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PrivacySubjectRequest extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'privacy_subject_requests';

    protected $fillable = [
        'tenant_id',
        'subject_identity_id',
        'request_type',
        'status',
        'reason',
        'correction_payload',
        'completed_at',
        'handled_by',
    ];

    protected $casts = [
        'correction_payload' => 'array',
        'completed_at' => 'datetime',
    ];
}

