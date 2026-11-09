<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Message template registry (E1 / E4).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $category
 * @property string $channel
 * @property string $locale
 * @property string $template_name
 * @property string $body
 * @property array<string, mixed>|null $variables
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class MessageTemplate extends Model
{
    use BelongsToTenant;
    use HasUlids;

    protected $connection = 'tenant_shared';

    protected $table = 'message_templates';

    protected $fillable = [
        'tenant_id',
        'category',
        'channel',
        'locale',
        'template_name',
        'body',
        'variables',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
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
