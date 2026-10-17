<?php

namespace App\Platform\Identity\OAuth\Console;

use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

/**
 * Create an OAuth client with its kernel profile: `platform:client:create
 * --name= --type= --tenant= --grants= --redirect=* --scope=*`. The plaintext
 * secret prints ONCE (hashed at rest) — capture it now; rotation is a delete +
 * recreate until the rotation command lands.
 *
 * Types: first_party_web (code + refresh, PKCE), first_party_service
 * (client credentials), partner (client credentials + tenant REQUIRED),
 * pos_device (device-code + refresh + tenant REQUIRED, one row per
 * terminal — a compromised terminal revokes individually).
 */
final class CreateClientCommand extends Command
{
    protected $signature = 'platform:client:create
        {--name= : Human-readable client name}
        {--type=first_party_web : first_party_web|first_party_service|partner|pos_device}
        {--tenant= : Tenant id (required for partner clients)}
        {--grants= : Comma-separated grants (defaults per type)}
        {--redirect=* : Registered redirect URIs (first_party_web)}
        {--scope=* : Allowed scopes (the A5 ceiling — empty allows NOTHING, fail closed; openid for BFF web clients)}
        {--post-logout-redirect-uri=* : Registered post-logout redirect URIs (RP-initiated logout landing zones)}
        {--backchannel-logout-uri= : OIDC back-channel logout receiver endpoint (http(s) URL)}
        {--require-par : Mandate pushed authorization requests (RFC 9126) for this client}';

    protected $description = 'Create an OAuth client with its kernel profile (secret shown once)';

    /**
     * @var array<string, list<string>>
     */
    private const GRANTS_FOR_TYPE = [
        OAuthClientProfile::TYPE_FIRST_PARTY_WEB => ['authorization_code', 'refresh_token'],
        OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE => ['client_credentials'],
        OAuthClientProfile::TYPE_PARTNER => ['client_credentials'],
        OAuthClientProfile::TYPE_POS_DEVICE => ['urn:ietf:params:oauth:grant-type:device_code', 'refresh_token'],
    ];

    public function handle(): int
    {
        $type = (string) $this->option('type');

        if (! in_array($type, OAuthClientProfile::TYPES, true)) {
            $this->error('Unknown client type.');
            $this->line('Valid: '.implode(', ', OAuthClientProfile::TYPES));

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: $type.' client');
        $tenantId = $this->option('tenant') !== null ? (string) $this->option('tenant') : null;

        if (in_array($type, [OAuthClientProfile::TYPE_PARTNER, OAuthClientProfile::TYPE_POS_DEVICE], true)) {
            $tenant = $tenantId !== null ? Tenant::query()->find($tenantId) : null;
            if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
                $label = $type === OAuthClientProfile::TYPE_PARTNER ? 'Partner' : 'POS device';

                $this->error($label.' clients require an active --tenant.'.($type === OAuthClientProfile::TYPE_POS_DEVICE ? ' (one record per terminal)' : ''));

                return self::FAILURE;
            }
            $tenantId = (string) $tenant->getKey();
        }

        $grants = $this->option('grants') !== null && $this->option('grants') !== ''
            ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('grants')))))
            : self::GRANTS_FOR_TYPE[$type];

        if ($grants === []) {
            $this->error('At least one grant is required.');

            return self::FAILURE;
        }

        // A5 ceiling: without --scope the profile allows NOTHING
        // (EnforcesProfileScopes fails closed — parity with the admin
        // surface's allowed_scopes, which the console previously had
        // no way to set).
        $scopeValues = array_map('strval', (array) $this->option('scope'));
        $scopes = array_values(array_filter(
            array_map('trim', $scopeValues),
            static fn (string $scope): bool => $scope !== '',
        ));

        $secret = Str::random(48);

        // A-SEC-004-6 console parity with the client admin surface:
        // logout endpoints are registered http(s) URLs only — the
        // dispatcher re-checks the scheme before any outbound request,
        // and registration (here or over HTTP) never loosens it.
        $postLogoutUris = array_values(array_filter(array_map('strval', (array) $this->option('post-logout-redirect-uri'))));

        foreach ($postLogoutUris as $uri) {
            if (! preg_match('#^https?://.+#i', $uri)) {
                $this->error('Post-logout redirect URIs must be http(s) URLs.');

                return self::FAILURE;
            }
        }

        $backchannelUri = $this->option('backchannel-logout-uri') !== null
            ? (string) $this->option('backchannel-logout-uri')
            : null;

        if ($backchannelUri !== null && ! preg_match('#^https?://.+#i', $backchannelUri)) {
            $this->error('The back-channel logout URI must be an http(s) URL.');

            return self::FAILURE;
        }

        $client = new Client();
        $client->forceFill([
            'name' => $name,
            'secret' => $secret,
            'redirect_uris' => $this->option('redirect'),
            'grant_types' => $grants,
            'revoked' => false,
        ]);
        $client->save();

        $profile = new OAuthClientProfile();
        $profile->forceFill([
            'client_id' => (string) $client->getKey(),
            'client_type' => $type,
            'tenant_id' => $tenantId,
            'allowed_grants' => $grants,
            'allowed_scopes' => $scopes,
            'require_pkce' => true,
            'require_par' => (bool) $this->option('require-par'),
            'post_logout_redirect_uris' => $postLogoutUris,
            'backchannel_logout_uri' => $backchannelUri,
            'rate_tier' => 'standard',
        ]);
        $profile->save();

        $this->info('Client created: '.(string) $client->getKey());
        $this->line('Secret (shown once): '.$secret);
        $this->line('Grants: '.implode(', ', $grants));

        if ($scopes !== []) {
            $this->line('Allowed scopes: '.implode(', ', $scopes));
        } else {
            $this->comment('Allowed scopes: NONE (fail closed — pass --scope=... to permit requested scopes)');
        }

        if ($backchannelUri !== null) {
            $this->line('Back-channel logout: '.$backchannelUri);
        }

        if ($postLogoutUris !== []) {
            $this->line('Post-logout redirects: '.implode(', ', $postLogoutUris));
        }

        return self::SUCCESS;
    }
}
