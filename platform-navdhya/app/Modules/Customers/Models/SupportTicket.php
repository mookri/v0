<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Support ticket row (tenant_shared, customer side).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $subject
 * @property string $category
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class SupportTicket extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_CLOSED = 'CLOSED';

    public const MAX_OPEN_PER_CUSTOMER = 10;

    /** @var list<string> */
    public const CATEGORIES = ['billing', 'booking', 'account', 'other'];

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'user_id', 'subject', 'category', 'status',
    ];

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
     * @return Factory<SupportTicket>
     */
    protected static function newFactory(): Factory
    {
        return SupportTicketFactory::new();
    }
}
