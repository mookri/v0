<?php

namespace App\Modules\Recommendations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $reco_impression_id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $entity_type
 * @property string $entity_id
 * @property string $interaction_type
 * @property int $dwell_ms
 * @property \Carbon\Carbon $interacted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class RecoInteraction extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'reco_interactions';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'reco_impression_id',
        'user_id',
        'session_id',
        'entity_type',
        'entity_id',
        'interaction_type',
        'dwell_ms',
        'interacted_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'dwell_ms' => 'integer',
        'interacted_at' => 'datetime',
    ];
}

