<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $entity_type
 * @property string $entity_id
 * @property int $revision
 * @property string $operation
 * @property string $status
 * @property int $attempts
 * @property array<string, mixed>|null $payload_json
 * @property \Carbon\Carbon|null $lease_expires_at
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SearchIndexOutbox extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';

    public const OP_UPSERT = 'UPSERT';
    public const OP_DELETE = 'DELETE';

    protected $connection = 'tenant_shared';
    protected $table = 'search_index_outbox';
    protected $keyType = 'ulid';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'entity_id',
        'revision',
        'operation',
        'status',
        'attempts',
        'payload_json',
        'lease_expires_at',
        'error_message',
    ];

    protected $casts = [
        'revision' => 'integer',
        'attempts' => 'integer',
        'payload_json' => 'array',
        'lease_expires_at' => 'datetime',
    ];
}
