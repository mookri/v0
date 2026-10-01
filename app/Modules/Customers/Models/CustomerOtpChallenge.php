<?php

namespace App\Modules\Customers\Models;

use Database\Factories\CustomerOtpChallengeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer OTP challenge row (central, deliberately scopeless — see the
 * migration note: public auth routes have no tenant context, so every
 * query predicates tenant_id explicitly and callers validate the tenant
 * is ACTIVE before touching a row).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $purpose
 * @property string $identifier
 * @property string $code_hash
 * @property array<string, mixed>|null $payload
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int $attempts
 * @property int $max_attempts
 */
final class CustomerOtpChallenge extends Model
{
    /** @use HasFactory<CustomerOtpChallengeFactory> */
    use HasFactory;
    use HasUlids;

    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_SIGNUP = 'signup';

    public const PURPOSE_VERIFY_PHONE = 'verify_phone';

    public const PURPOSE_VERIFY_EMAIL = 'verify_email';

    public const PURPOSE_CHANGE_PHONE = 'change_phone';

    public const PURPOSE_CHANGE_EMAIL = 'change_email';

    /** @var list<string> */
    public const PURPOSES = [
        self::PURPOSE_LOGIN,
        self::PURPOSE_SIGNUP,
        self::PURPOSE_VERIFY_PHONE,
        self::PURPOSE_VERIFY_EMAIL,
        self::PURPOSE_CHANGE_PHONE,
        self::PURPOSE_CHANGE_EMAIL,
    ];

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id', 'purpose', 'identifier', 'code_hash', 'payload',
        'expires_at', 'attempts', 'max_attempts',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return Factory<CustomerOtpChallenge>
     */
    protected static function newFactory(): Factory
    {
        return CustomerOtpChallengeFactory::new();
    }
}
