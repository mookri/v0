<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Devotee family member & ancestral lineage (E1).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $full_name
 * @property string $relation
 * @property string|null $nakshatra
 * @property string|null $rashi
 * @property string|null $birth_date
 * @property string|null $death_date
 * @property string $data_subject_kind
 * @property string|null $consent_ref
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class CustomerFamilyMember extends Model
{
    use BelongsToTenant;
    use HasUlids;
    use SoftDeletes;

    public const KIND_SELF = 'SELF';
    public const KIND_DECEASED = 'DECEASED';
    public const KIND_THIRD_PARTY = 'THIRD_PARTY';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'full_name',
        'relation',
        'nakshatra',
        'rashi',
        'birth_date',
        'death_date',
        'data_subject_kind',
        'consent_ref',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    public function isDeceased(): bool
    {
        return $this->data_subject_kind === self::KIND_DECEASED;
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
