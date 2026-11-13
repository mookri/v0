<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $delete_form
 * @property string $original_key
 * @property string $scheme
 * @property int $edits
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchDelete extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_deletes';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'delete_form',
        'original_key',
        'scheme',
        'edits',
    ];

    protected $casts = [
        'edits' => 'integer',
    ];
}
