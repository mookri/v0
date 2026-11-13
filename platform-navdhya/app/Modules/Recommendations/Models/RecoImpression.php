<?php

namespace App\Modules\Recommendations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $surface
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $entity_type
 * @property string $entity_id
 * @property int $position
 * @property string $reason_code
 * @property \Carbon\Carbon $impressed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class RecoImpression extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'reco_impressions';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'surface',
        'user_id',
        'session_id',
        'entity_type',
        'entity_id',
        'position',
        'reason_code',
        'impressed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'position' => 'integer',
        'impressed_at' => 'datetime',
    ];
}

