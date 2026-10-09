<?php

namespace App\Platform\Identity\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Persisted HMAC actor-token session (M5 / TOKEN-001): one row per minted
 * jti. Revocation stamps revoked_at (a deleted or missing row has the
 * same effect — ActorTokenVerifier fails closed either way, so the
 * sweeper may delete revoked rows at leisure).
 */
final class ActorSession extends Model
{
    protected $connection = 'central';

    public $incrementing = false;

    /** @var string */
    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'issued_at', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Revoke one presented session id (logout, rotation predecessor).
     */
    public static function revoke(string $sessionId): void
    {
        self::query()
            ->whereKey($sessionId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => gmdate('Y-m-d H:i:s')]);
    }

    /**
     * Revoke every live session of an actor (external_id) — logout-all /
     * revoke-others / suspension. Returns rows revoked.
     */
    public static function revokeForUser(string $externalUserId): int
    {
        return self::query()
            ->where('user_id', $externalUserId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => gmdate('Y-m-d H:i:s')]);
    }
}
