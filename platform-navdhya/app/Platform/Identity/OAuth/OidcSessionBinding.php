<?php

namespace App\Platform\Identity\OAuth;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * OIDC session<->client binding (central, plain model — identity-owned
 * rows are never tenant-scoped; every read predicates explicitly).
 * Written at openid authorization time (SEC-004 / A-SEC-004-5),
 * enumerated at OP-session termination for back-channel logout.
 *
 * @property string $id
 * @property string $sid
 * @property string $client_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $notified_at
 */
final class OidcSessionBinding extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'oidc_session_bindings';

    /** @var list<string> */
    protected $fillable = [
        'id', 'sid', 'client_id', 'user_id', 'expires_at', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }
}
