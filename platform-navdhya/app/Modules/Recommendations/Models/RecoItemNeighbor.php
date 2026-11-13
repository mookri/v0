<?php

namespace App\Modules\Recommendations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $source_type
 * @property string $source_id
 * @property string $neighbor_type
 * @property string $neighbor_id
 * @property float $sim_final
 * @property string $relation_kind
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class RecoItemNeighbor extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'reco_item_neighbors';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'source_type',
        'source_id',
        'neighbor_type',
        'neighbor_id',
        'sim_final',
        'relation_kind',
    ];

    protected $casts = [
        'sim_final' => 'float',
    ];
}

