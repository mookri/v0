<?php

namespace App\Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Paging cursor for downstream warehouse signal sync (E1 / E5).
 *
 * @property string $feed_name
 * @property string|null $last_processed_ulid
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class EventsExportCursor extends Model
{
    public const CREATED_AT = null;

    protected $connection = 'tenant_shared';

    protected $table = 'events_export_cursors';

    protected $primaryKey = 'feed_name';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'feed_name',
        'last_processed_ulid',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }
}
