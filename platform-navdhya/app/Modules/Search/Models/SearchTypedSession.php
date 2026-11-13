<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $session_id
 * @property string|null $trace_id
 * @property string $query_text
 * @property int $keystroke_count
 * @property int $backspace_count
 * @property bool $ime_composition
 * @property int $time_to_first_impression_ms
 * @property int $total_typing_ms
 * @property \Carbon\Carbon|null $settled_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchTypedSession extends Model
{
    use HasUlids;

    protected $connection = 'central';
    protected $table = 'search_typed_sessions';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'session_id',
        'trace_id',
        'query_text',
        'keystroke_count',
        'backspace_count',
        'ime_composition',
        'time_to_first_impression_ms',
        'total_typing_ms',
        'settled_at',
    ];

    protected $casts = [
        'keystroke_count' => 'integer',
        'backspace_count' => 'integer',
        'ime_composition' => 'boolean',
        'time_to_first_impression_ms' => 'integer',
        'total_typing_ms' => 'integer',
        'settled_at' => 'datetime',
    ];
}

