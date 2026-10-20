<?php

namespace App\Platform\Identity\OAuth;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Back-channel logout delivery attempt row (central, plain model —
 * identity-owned rows are never tenant-scoped). The outbox the
 * dispatcher fills at session termination and
 * auth:retry-backchannel-logout drains (SEC-004 / A-SEC-004-5). The
 * logout token is re-minted per attempt — never stored here.
 *
 * @property string $id
 * @property string $sid
 * @property string $client_id
 * @property string $logout_uri
 * @property string $subject
 * @property string $state
 * @property int $attempts
 * @property \Illuminate\Support\Carbon $next_attempt_at
 * @property string|null $last_error
 * @property \Illuminate\Support\Carbon|null $delivered_at
 */
final class OidcBackchannelDelivery extends Model
{
    use HasUlids;

    public const STATE_PENDING = 'pending';

    public const STATE_DELIVERED = 'delivered';

    public const STATE_FAILED = 'failed';

    protected $connection = 'central';

    protected $table = 'oidc_backchannel_deliveries';

    /** @var list<string> */
    protected $fillable = [
        'id', 'sid', 'client_id', 'logout_uri', 'subject', 'state',
        'attempts', 'next_attempt_at', 'last_error', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'next_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}
