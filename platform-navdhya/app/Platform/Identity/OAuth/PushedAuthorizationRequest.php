<?php

namespace App\Platform\Identity\OAuth;

use Database\Factories\PushedAuthorizationRequestFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pushed authorization request (RFC 9126, central, plain model —
 * identity-owned rows are never tenant-scoped).
 *
 * The request_uri is a single-use bearer capability: only its SHA-256
 * hash is stored, so a database read never yields a redeemable
 * request_uri. Redemption is an atomic `used_at` claim — the
 * authorization endpoint consumes exactly once; a replayed request_uri
 * (second claim, expiry, wrong client) fails closed.
 */
final class PushedAuthorizationRequest extends Model
{
    /** @use HasFactory<PushedAuthorizationRequestFactory> */
    use HasFactory;
    use HasUlids;

    /** RFC 9126 §2.1 recommendation: push TTL is minutes, never hours. */
    public const TTL_SECONDS = 120;

    public const REQUEST_URI_PREFIX = 'urn:ietf:params:oauth:request_uri:';

    protected $connection = 'central';

    protected $table = 'pushed_authorization_requests';

    // request_uri_hash is an internal write column (mint() computes it
    // from the capability value) — same posture as AuthEvent's hash.
    // used_at is deliberately NOT fillable: consumption only happens
    // through the atomic claim() update.
    protected $fillable = [
        'client_id', 'params', 'expires_at', 'request_uri_hash',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * @return Factory<PushedAuthorizationRequest>
     */
    protected static function newFactory(): Factory
    {
        return PushedAuthorizationRequestFactory::new();
    }

    /**
     * The capability value handed to the client (stored only as a
     * hash — see the migration).
     */
    public static function requestUriFor(string $id): string
    {
        return self::REQUEST_URI_PREFIX.$id;
    }

    /**
     * Mint a request_uri for a validated push: the row stores only the
     * hash of the capability, and the raw urn is returned once — the
     * client must present it back complete for redemption.
     *
     * @param  array<string, mixed>  $params  the full (validated) authorization request
     * @return array{0: self, 1: string}
     */
    public static function mint(string $clientId, array $params): array
    {
        $id = (string) \Illuminate\Support\Str::ulid();
        $requestUri = self::requestUriFor($id);

        $row = new self();
        $row->forceFill([
            'id' => $id,
            'request_uri_hash' => hash('sha256', $requestUri),
            'client_id' => $clientId,
            'params' => $params,
            'expires_at' => now()->addSeconds(self::TTL_SECONDS),
        ]);
        $row->save();

        return [$row, $requestUri];
    }

    /**
     * Resolve a presented request_uri to its row — hash lookup, never
     * a raw-value index (the urn is the bearer capability).
     */
    public static function resolve(string $requestUri): ?self
    {
        if (! str_starts_with($requestUri, self::REQUEST_URI_PREFIX)) {
            return null;
        }

        return self::query()
            ->where('request_uri_hash', hash('sha256', $requestUri))
            ->first();
    }

    /**
     * Atomic single-use claim: succeeds exactly once per row, no matter
     * how many authorization requests race the same request_uri.
     */
    public function claim(): bool
    {
        $claimed = self::query()
            ->whereKey($this->getKey())
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        return $claimed === 1;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
