<?php

namespace App\Modules\Recommendations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $user_id
 * @property string $interest_kind
 * @property string $interest_key
 * @property float $weight
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class RecoUserInterest extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'reco_user_interests';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'interest_kind',
        'interest_key',
        'weight',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'weight' => 'float',
    ];
}

