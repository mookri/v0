<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\SavedItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Saved-item bookmark (tenant_shared): ref_type + ref_id are opaque
 * catalog/content references, shape-validated at write, enriched by
 * readers. Never a foreign key — dangling refs resolve to null upstream.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $ref_type
 * @property string $ref_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class SavedItem extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<SavedItemFactory> */
    use HasFactory;

    /** @var list<string> */
    public const REF_TYPES = ['puja', 'content', 'service'];

    protected $connection = 'tenant_shared';

    protected $fillable = ['tenant_id', 'user_id', 'ref_type', 'ref_id'];

    public function getConnectionName(): ?string
    {
        try {
            $context = app(\App\Platform\Tenancy\TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }

    /**
     * @return Factory<SavedItem>
     */
    protected static function newFactory(): Factory
    {
        return SavedItemFactory::new();
    }
}
