<?php

namespace App\Domain\Verification\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Practical ritual competency assessment / sampradaya peer attestation (T-TR03-05).
 */
final class SampradayaAttestation extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'sampradaya_attestations';

    protected $guarded = [];

    protected $casts = [
        'scope_tags_json' => 'array',
        'rubric_scores_json' => 'array',
        'overall_score' => 'integer',
        'passed' => 'boolean',
        'assessed_at' => 'datetime',
    ];
}

