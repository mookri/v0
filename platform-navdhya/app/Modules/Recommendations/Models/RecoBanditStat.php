<?php

namespace App\Modules\Recommendations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $entity_type
 * @property string $entity_id
 * @property int $alpha
 * @property int $beta
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class RecoBanditStat extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'reco_bandit_stats';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'alpha',
        'beta',
    ];

    protected $casts = [
        'alpha' => 'integer',
        'beta' => 'integer',
    ];
}

