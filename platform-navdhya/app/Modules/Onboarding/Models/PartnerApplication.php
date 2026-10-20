<?php

namespace App\Modules\Onboarding\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Partner application row (onboarding stage 1 port): tenant-scoped,
 * version-pinned track, lifecycle states DRAFT→SUBMITTED→UNDER_REVIEW→
 * APPROVED/REJECTED (+APPEALED/WITHDRAWN). Connection follows the ambient
 * tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $identity_id
 * @property string $track
 * @property string $track_version
 * @property string $status
 * @property string $market
 * @property string|null $supersedes
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property string|null $reviewer_id
 * @property string|null $rejection_code
 * @property array<string, mixed>|null $evidence
 */
final class PartnerApplication extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<\Database\Factories\PartnerApplicationFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_APPEALED = 'APPEALED';

    public const STATUS_WITHDRAWN = 'WITHDRAWN';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'identity_id', 'track', 'track_version', 'status',
        'market', 'supersedes', 'submitted_at', 'reviewed_at',
        'reviewer_id', 'rejection_code', 'evidence',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'evidence' => 'array',
        ];
    }

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
     * @return \Database\Factories\PartnerApplicationFactory
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return \Database\Factories\PartnerApplicationFactory::new();
    }
}
