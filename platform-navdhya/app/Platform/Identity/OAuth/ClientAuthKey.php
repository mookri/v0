<?php

namespace App\Platform\Identity\OAuth;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Registered client authentication public key (central, plain model —
 * identity-owned rows are never tenant-scoped; every read predicates
 * explicitly).
 *
 * Holds ONLY the public half (D3): possession of this row cannot mint
 * tokens or assertions, it can only verify them. Revocation keeps the
 * row (`revoked_at`) so audit and forensics survive the kill.
 *
 * @property string $id
 * @property string $client_id
 * @property string $kid
 * @property string $public_key
 * @property \Illuminate\Support\Carbon|null $revoked_at
 */
final class ClientAuthKey extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $table = 'client_auth_keys';

    protected $fillable = [
        'client_id', 'kid', 'public_key', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }
}
