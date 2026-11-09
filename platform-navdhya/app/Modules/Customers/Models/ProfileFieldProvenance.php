<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Devotee profile field provenance and confidence (E1 / E5).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $field_name
 * @property string $source
 * @property int $confidence
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ProfileFieldProvenance extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const SOURCE_USER_ENTERED = 'USER_ENTERED';
    public const SOURCE_INFERRED = 'INFERRED';
    public const SOURCE_DERIVED = 'DERIVED';
    public const SOURCE_THIRD_PARTY = 'THIRD_PARTY';

    protected $connection = 'tenant_shared';

    protected $table = 'profile_field_provenance';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'field_name',
        'source',
        'confidence',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'confidence' => 'integer',
            'expires_at' => 'datetime',
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
