<?php

namespace App\Modules\Temple\Models;

use App\Domain\Verification\Concerns\BlocksDirectVerificationStatusWrite;
use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\TempleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Temple (tenant_shared): temple profiles owned by partners.
 * Connection follows the ambient tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $slug
 * @property string $name
 * @property string $trust_name
 * @property string $type
 * @property string $address
 * @property string $city
 * @property string $state
 * @property float|null $lat
 * @property float|null $lng
 * @property string $timezone
 * @property int|null $daily_booking_cap
 * @property list<string> $images
 * @property list<string> $amenities
 * @property string|null $description
 * @property string $status
 * @property string $verification_status
 * @property string $owner_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static TempleFactory factory()
 */
final class Temple extends Model
{
    use BelongsToTenant;
    use BlocksDirectVerificationStatusWrite;
    use HasUlids;
    /** @use HasFactory<TempleFactory> */
    use HasFactory;

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_TRUST = 'trust';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public const VERIFICATION_PENDING = 'PENDING';
    public const VERIFICATION_VERIFIED = 'VERIFIED';
    public const VERIFICATION_REJECTED = 'REJECTED';

    protected $connection = 'tenant_shared';

    protected $fillable = [
        'tenant_id',
        'slug',
        'name',
        'trust_name',
        'type',
        'address',
        'city',
        'state',
        'lat',
        'lng',
        'timezone',
        'daily_booking_cap',
        'images',
        'amenities',
        'description',
        'status',
        'verification_status',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'amenities' => 'array',
            'lat' => 'float',
            'lng' => 'float',
            'daily_booking_cap' => 'integer',
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
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    /**
     * @return HasMany<\App\Modules\Temple\Models\TemplePuja, $this>
     */
    public function pujas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Temple\Models\TemplePuja::class, 'temple_id');
    }

    /**
     * @return HasMany<\App\Modules\Temple\Models\TempleManager, $this>
     */
    public function managers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Temple\Models\TempleManager::class, 'temple_id');
    }

    /**
     * @return HasMany<TempleDeity, $this>
     */
    public function deities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TempleDeity::class, 'temple_id', 'id');
    }

    /**
     * @return HasMany<TempleAartiSchedule, $this>
     */
    public function aartis(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TempleAartiSchedule::class, 'temple_id', 'id');
    }

    /**
     * @return HasMany<TempleSanctumRule, $this>
     */
    public function sanctumRules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TempleSanctumRule::class, 'temple_id', 'id');
    }

    /**
     * @return HasMany<TempleVenueLimit, $this>
     */
    public function venueLimits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TempleVenueLimit::class, 'temple_id', 'id');
    }

    /**
     * @return HasMany<TemplePriest, $this>
     */
    public function priests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TemplePriest::class, 'temple_id', 'id');
    }

    /**
     * @return HasMany<TempleBookingApproval, $this>
     */
    public function approvals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TempleBookingApproval::class, 'temple_id', 'id');
    }

    /**
     * @return array<string>
     */
    public static function getAvailableAmenities(): array
    {
        return [
            'parking',
            'wheelchair_access',
            'prasad_counter',
            'shoe_rack',
            'drinking_water',
            'restroom',
            'cloakroom',
            'elevator',
            'wifi',
            'ac',
            'changing_room',
            'locker',
        ];
    }

    /**
     * @return Factory<Temple>
     */
    protected static function newFactory(): Factory
    {
        return TempleFactory::new();
    }
}
