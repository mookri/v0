<?php

namespace App\Modules\Recommendations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $tradition
 * @property string|null $gotra
 * @property string|null $preferred_region_id
 * @property string $preferred_language
 * @property \Carbon\Carbon|null $last_active_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class RecoUserProfile extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'reco_user_profiles';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'tradition',
        'gotra',
        'preferred_region_id',
        'preferred_language',
        'last_active_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'last_active_at' => 'datetime',
    ];
}

