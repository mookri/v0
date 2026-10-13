<?php

namespace App\Modules\Customers\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Firebase phone-login intent row (central, deliberately scopeless — see
 * the migration note: public auth routes have no tenant context, so every
 * query predicates tenant_id explicitly and callers validate the tenant
 * is ACTIVE before touching a row).
 *
 * Single-use: exchange deletes the row it consumes under the same
 * predicates it loaded it with (a single atomic DELETE), so a concurrent
 * second exchange finds nothing and collapses to the uniform 401.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $purpose
 * @property string $phone
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class FirebasePhoneAuthIntent extends Model
{
    use HasUlids;

    public const PURPOSE_LOGIN = 'login';

    /** Short-lived by design: the row only spans the client's Firebase handshake. */
    public const TTL_SECONDS = 600;

    protected $connection = 'central';

    protected $table = 'firebase_phone_auth_intents';

    /** @var list<string> */
    protected $fillable = ['tenant_id', 'purpose', 'phone', 'expires_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
