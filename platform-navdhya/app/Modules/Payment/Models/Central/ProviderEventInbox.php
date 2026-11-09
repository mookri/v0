<?php

namespace App\Modules\Payment\Models\Central;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Central Provider Event Inbox (Phase 4, Task 4.1.2).
 *
 * @property string $id
 * @property string $provider_code
 * @property string $event_id
 * @property string $event_type
 * @property string $raw_payload
 * @property array<string, mixed> $headers_json
 * @property string|null $signature
 * @property string|null $tenant_id
 * @property string $status
 * @property string|null $error_detail
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ProviderEventInbox extends Model
{
    use HasUlids;

    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_VERIFIED = 'VERIFIED';
    public const STATUS_PROCESSED = 'PROCESSED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_DUPLICATE = 'DUPLICATE';

    protected $connection = 'central';

    protected $table = 'provider_event_inbox';

    protected $fillable = [
        'provider_code',
        'event_id',
        'event_type',
        'raw_payload',
        'headers_json',
        'signature',
        'tenant_id',
        'status',
        'error_detail',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'headers_json' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
