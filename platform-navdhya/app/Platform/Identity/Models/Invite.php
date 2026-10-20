<?php

namespace App\Platform\Identity\Models;

use Database\Factories\InviteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Track-scoped invite (central): token stored as SHA-256 hash, single-use
 * lifecycle ISSUED → ACCEPTED/EXPIRED/REVOKED, per-track seeding on accept.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $track
 * @property array<string>|null $roles
 * @property string $identifier
 * @property string $token_hash
 * @property string $state
 * @property \Illuminate\Support\Carbon $expires_at
 * @property string|null $accepted_by
 * @property string|null $created_by
 */
final class Invite extends Model
{
    /** @use HasFactory<InviteFactory> */
    use HasFactory;
    use HasUlids;

    public const STATE_ISSUED = 'ISSUED';

    public const STATE_ACCEPTED = 'ACCEPTED';

    public const STATE_EXPIRED = 'EXPIRED';

    public const STATE_REVOKED = 'REVOKED';

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id', 'track', 'roles', 'identifier',
        'token_hash', 'state', 'expires_at', 'accepted_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return Factory<Invite>
     */
    protected static function newFactory(): Factory
    {
        return InviteFactory::new();
    }
}
