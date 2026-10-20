<?php

namespace App\Modules\Customers\Models;

use App\Platform\Tenancy\BelongsToTenant;
use Database\Factories\ReferralCodeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Referral code row (tenant_shared): one per customer, globally unique.
 * Codes use an unambiguous alphabet (no 0/O/1/I) with a REF- prefix.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ReferralCode extends Model
{
    use BelongsToTenant;
    use HasUlids;
    /** @use HasFactory<ReferralCodeFactory> */
    use HasFactory;

    public const PREFIX = 'REF-';

    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const LENGTH = 8;

    protected $connection = 'tenant_shared';

    protected $fillable = ['tenant_id', 'user_id', 'code'];

    public function getConnectionName(): ?string
    {
        try {
            $context = app(\App\Platform\Tenancy\TenantContext::class);
        } catch (\Throwable) {
            return $this->connection;
        }

        return $context->connectionName() ?? $this->connection;
    }

    public static function generate(): string
    {
        $code = self::PREFIX;
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * @return Factory<ReferralCode>
     */
    protected static function newFactory(): Factory
    {
        return ReferralCodeFactory::new();
    }
}
