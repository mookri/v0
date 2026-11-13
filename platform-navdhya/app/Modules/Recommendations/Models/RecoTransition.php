<?php

namespace App\Modules\Recommendations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $source_id
 * @property string $target_id
 * @property int $count
 * @property float $p_estimate
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class RecoTransition extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'reco_transitions';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'source_id',
        'target_id',
        'count',
        'p_estimate',
    ];

    protected $casts = [
        'count' => 'integer',
        'p_estimate' => 'float',
    ];
}

