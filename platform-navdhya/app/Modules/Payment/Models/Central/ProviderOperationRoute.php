<?php

namespace App\Modules\Payment\Models\Central;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Central Provider Operation Route (Phase 4, Task 4.1.1).
 *
 * @property string $id
 * @property string $provider_code
 * @property string $provider_operation_id
 * @property string $tenant_id
 * @property string $connection_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ProviderOperationRoute extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'provider_operation_routes';

    protected $fillable = [
        'provider_code',
        'provider_operation_id',
        'tenant_id',
        'connection_name',
    ];
}

