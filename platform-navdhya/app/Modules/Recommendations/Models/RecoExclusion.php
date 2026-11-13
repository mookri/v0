<?php

namespace App\Modules\Recommendations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $user_id
 * @property string $entity_type
 * @property string $entity_id
 * @property string $reason
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class RecoExclusion extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'reco_exclusions';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'reason',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'expires_at' => 'datetime',
    ];
}

