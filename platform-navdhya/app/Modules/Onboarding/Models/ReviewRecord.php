<?php

namespace App\Modules\Onboarding\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Review decision record (append-only audit evidence).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $application_id
 * @property string $reviewer_id
 * @property string $decision
 * @property string|null $reason_code
 * @property string|null $note
 */
final class ReviewRecord extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const DECISION_APPROVED = 'APPROVED';

    public const DECISION_REJECTED = 'REJECTED';

    public const DECISION_REQUEST_INFO = 'REQUEST_INFO';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'application_id', 'reviewer_id',
        'decision', 'reason_code', 'note',
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
}
