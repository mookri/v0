<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Devotee spiritual profile (E1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string|null $gotra
 * @property string|null $nakshatra
 * @property string|null $rashi
 * @property string|null $kuladevata_name
 * @property string|null $kuladevata_temple_id
 * @property string|null $birth_date
 * @property string|null $birth_time
 * @property string|null $birth_place
 * @property string $birth_timezone
 * @property string $tradition
 * @property \Illuminate\Support\Carbon|null $retention_expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class CustomerSpiritualProfile extends Model
{
    use BelongsToTenant;
    use HasUlids;
    use SoftDeletes;

    public const TRADITION_AMANTA = 'AMANTA';
    public const TRADITION_PURNIMANTA = 'PURNIMANTA';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'gotra',
        'nakshatra',
        'rashi',
        'kuladevata_name',
        'kuladevata_temple_id',
        'birth_date',
        'birth_time',
        'birth_place',
        'birth_timezone',
        'tradition',
        'retention_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'retention_expires_at' => 'datetime',
        ];
    }

    public function getConnectionName(): ?string
    {
        try {
            $context = app(TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }
}
