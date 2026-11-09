<?php

namespace App\Modules\Pandit\Models;

use App\Modules\Pandit\Contracts\VedicTradition;
use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\PanditProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property VedicTradition|string|null $tradition
 * @property string|null $gotra
 * @property string|null $shakha_code
 * @property string|null $bio
 * @property list<string>|null $languages
 * @property list<string>|null $specializations
 * @property int|null $experience_years
 * @property list<string>|null $certification_refs
 * @property string $verification_status
 * @property list<array<string, mixed>>|null $verification_documents
 * @property int $travel_buffer_minutes
 * @property int $travel_radius_km
 * @property string $timezone
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
        'tenant_id', 'user_id', 'display_name',
        'tradition', 'gotra', 'shakha_code',
        'bio', 'languages',
        'specializations', 'experience_years', 'certification_refs',
        'verification_status', 'verification_documents',
        'travel_buffer_minutes', 'travel_radius_km', 'timezone',
        'base_rate_minor', 'currency', 'market',
        'rating_avg', 'rating_count', 'total_bookings',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tradition' => VedicTradition::class,
            'languages' => 'array',
            'specializations' => 'array',
            'certification_refs' => 'array',
            'verification_documents' => 'array',
            'travel_buffer_minutes' => 'integer',
            'travel_radius_km' => 'integer',
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
     * @return BelongsTo<PanditShakha, $this>
     */
    public function shakha(): BelongsTo
    {
        return $this->belongsTo(PanditShakha::class, 'shakha_code', 'code');
    }

    /**
     * @return HasMany<PanditBlackout, $this>
     */
    public function blackouts(): HasMany
    {
        return $this->hasMany(PanditBlackout::class, 'pandit_id', 'id');
    }

    /**
     * @return HasMany<PanditServiceEligibility, $this>
     */
    public function eligibilities(): HasMany
    {
        return $this->hasMany(PanditServiceEligibility::class, 'pandit_id', 'id');
    }

    /**
     * @return HasOne<PanditAssignmentRule, $this>
     */
    public function assignmentRule(): HasOne
    {
        return $this->hasOne(PanditAssignmentRule::class, 'pandit_id', 'id');
    }

    /**
     * @return Factory<PanditProfile>
     */
    protected static function newFactory(): Factory
    {
        return PanditProfileFactory::new();
    }
}
