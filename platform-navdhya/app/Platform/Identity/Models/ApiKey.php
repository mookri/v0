<?php

namespace App\Platform\Identity\Models;

use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Machine API key (central registry record): secret stored as SHA-256,
 * tenant-bound, scoped, tiered, expiring, revocable. Plaintext exists only
 * at issuance output — never persisted, never logged.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $prefix
 * @property string $secret_hash
 * @property array<string>|null $scopes
 * @property string $rate_tier
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property string|null $created_by
 */
final class ApiKey extends Model
{
    /** @use HasFactory<ApiKeyFactory> */
    use HasFactory;
    use HasUlids;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id', 'name', 'prefix', 'secret_hash', 'scopes',
        'rate_tier', 'expires_at', 'last_used_at', 'revoked_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return Factory<ApiKey>
     */
    protected static function newFactory(): Factory
    {
        return ApiKeyFactory::new();
    }

    public function isUsable(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
