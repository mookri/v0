<?php

namespace App\Platform\Identity\OAuth;

use Database\Factories\OAuthClientProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * OAuth client profile (central, plain model — identity-owned rows are
 * never tenant-scoped; every read predicates explicitly).
 *
 * @property string $id
 * @property string $client_id
 * @property string $client_type
 * @property string|null $tenant_id
 * @property array<string> $allowed_grants
 * @property array<string> $allowed_scopes
 * @property string|null $audience
 * @property int|null $access_ttl_seconds
 * @property int|null $refresh_ttl_seconds
 * @property bool $require_pkce
 * @property bool $require_par
 * @property bool $require_dpop
 * @property string $rate_tier
 * @property string $status
 * @property list<string>|null $post_logout_redirect_uris
 * @property string|null $backchannel_logout_uri
 */
final class OAuthClientProfile extends Model
{
    /** @use HasFactory<OAuthClientProfileFactory> */
    use HasFactory;
    use HasUlids;

    public const TYPE_FIRST_PARTY_WEB = 'first_party_web';

    public const TYPE_FIRST_PARTY_SERVICE = 'first_party_service';

    public const TYPE_PARTNER = 'partner';

    public const TYPE_POS_DEVICE = 'pos_device';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_FIRST_PARTY_WEB,
        self::TYPE_FIRST_PARTY_SERVICE,
        self::TYPE_PARTNER,
        self::TYPE_POS_DEVICE,
    ];

    protected $connection = 'central';

    protected $table = 'oauth_client_profiles';

    protected $fillable = [
        'client_id', 'client_type', 'tenant_id', 'allowed_grants',
        'allowed_scopes', 'audience', 'access_ttl_seconds',
        'refresh_ttl_seconds', 'require_pkce', 'require_par',
        'require_dpop', 'rate_tier', 'status',
        'post_logout_redirect_uris', 'backchannel_logout_uri',
    ];

    protected function casts(): array
    {
        return [
            'allowed_grants' => 'array',
            'allowed_scopes' => 'array',
            'require_pkce' => 'boolean',
            'require_par' => 'boolean',
            'require_dpop' => 'boolean',
            'post_logout_redirect_uris' => 'array',
        ];
    }

    /**
     * @return Factory<OAuthClientProfile>
     */
    protected static function newFactory(): Factory
    {
        return OAuthClientProfileFactory::new();
    }
}
