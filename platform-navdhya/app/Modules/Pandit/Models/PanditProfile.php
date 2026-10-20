<?php

namespace App\Modules\Pandit\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\PanditProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pandit profile (tenant_shared): the pandit's professional profile.
 * One per user per tenant, materialized at application approval.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $display_name
 * @property string|null $bio
 * @property list<string>|null $languages
 * @property list<string>|null $specializations
 * @property int|null $experience_years
 * @property list<string>|null $certification_refs
 * @property string $verification_status
 * @property list<array<string, mixed>>|null $verification_documents
 * @property int|null $base_rate_minor
 * @property string $currency
 * @property string $market
 * @property string $rating_avg
 * @property int $rating_count
 * @property int $total_bookings
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class PanditProfile extends Model
{
    use BelongsToTenant;
    use HasUlids;
    use SoftDeletes;
    /** @use HasFactory<PanditProfileFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const STATUS_DEACTIVATED = 'DEACTIVATED';

    public const VERIFICATION_PENDING = 'PENDING';

    public const VERIFICATION_DOCS_SUBMITTED = 'DOCS_SUBMITTED';

    public const VERIFICATION_INTERVIEW_SCHEDULED = 'INTERVIEW_SCHEDULED';

    public const VERIFICATION_VERIFIED = 'VERIFIED';

    public const VERIFICATION_REJECTED = 'REJECTED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id', 'user_id', 'display_name', 'bio', 'languages',
        'specializations', 'experience_years', 'certification_refs',
        'verification_status', 'verification_documents',
        'base_rate_minor', 'currency', 'market',
        'rating_avg', 'rating_count', 'total_bookings',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'specializations' => 'array',
            'certification_refs' => 'array',
            'verification_documents' => 'array',
            'base_rate_minor' => 'integer',
            'rating_avg' => 'decimal:1',
            'rating_count' => 'integer',
            'total_bookings' => 'integer',
            'deleted_at' => 'datetime',
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
     * Scope: only verified profiles.
     *
     * @param  Builder<PanditProfile>  $query
     * @return Builder<PanditProfile>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    /**
     * Scope: only active profiles.
     *
     * @param  Builder<PanditProfile>  $query
     * @return Builder<PanditProfile>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @return Factory<PanditProfile>
     */
    protected static function newFactory(): Factory
    {
        return PanditProfileFactory::new();
    }
}
