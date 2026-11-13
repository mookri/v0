<?php

namespace App\Domain\Privacy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PrivacyIncident extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'privacy_incidents';

    protected $fillable = [
        'tenant_id',
        'severity',
        'category',
        'summary',
        'affected_subjects_count',
        'detected_at',
        'reported_to_authority_at',
        'reported_by',
        'metadata',
    ];

    protected $casts = [
        'affected_subjects_count' => 'integer',
        'detected_at' => 'datetime',
        'reported_to_authority_at' => 'datetime',
        'metadata' => 'array',
    ];
}

