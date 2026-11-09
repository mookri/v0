<?php

namespace App\Modules\Temple\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temple Priest model (tenant_shared).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $temple_id
 * @property string|null $pandit_id
 * @property int $user_id
 * @property string $role
 * @property bool $council_member
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class TemplePriest extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const ROLE_HEAD_PRIEST = 'HEAD_PRIEST';
    public const ROLE_PRIEST = 'PRIEST';
    public const ROLE_ASSISTANT = 'ASSISTANT';

    protected $connection = 'tenant_shared';

    protected $table = 'temple_priests';

    protected $fillable = [
        'tenant_id',
        'temple_id',
        'pandit_id',
        'user_id',
        'role',
        'council_member',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'council_member' => 'boolean',
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

    /**
     * @return BelongsTo<Temple, $this>
     */
    public function temple(): BelongsTo
    {
        return $this->belongsTo(Temple::class, 'temple_id', 'id');
    }
}

