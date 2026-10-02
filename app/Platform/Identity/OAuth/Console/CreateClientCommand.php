<?php

namespace App\Platform\Identity\OAuth\Console;

use App\Platform\Identity\OAuth\OAuthClientProfile;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

/**
 * Create an OAuth client with its kernel profile: `platform:client:create
 * --name= --type= --tenant= --grants= --redirect=*`. The plaintext secret
 * prints ONCE (hashed at rest) — capture it now; rotation is a delete +
 * recreate until the rotation command lands.
 *
 * Types: first_party_web (code + refresh, PKCE), first_party_service
 * (client credentials), partner (client credentials + tenant REQUIRED).
 * pos_device is rejected: the device grant stays disabled (SEC-002).
 */
final class CreateClientCommand extends Command
{
    protected $signature = 'platform:client:create
        {--name= : Human-readable client name}
        {--type=first_party_web : first_party_web|first_party_service|partner|pos_device}
        {--tenant= : Tenant id (required for partner clients)}
        {--grants= : Comma-separated grants (defaults per type)}
        {--redirect=* : Registered redirect URIs (first_party_web)}';

    protected $description = 'Create an OAuth client with its kernel profile (secret shown once)';

    /**
     * @var array<string, list<string>>
     */
    private const GRANTS_FOR_TYPE = [
        OAuthClientProfile::TYPE_FIRST_PARTY_WEB => ['authorization_code', 'refresh_token'],
        OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE => ['client_credentials'],
        OAuthClientProfile::TYPE_PARTNER => ['client_credentials'],
    ];

    public function handle(): int
    {
        $type = (string) $this->option('type');

        if (! in_array($type, OAuthClientProfile::TYPES, true)) {
            $this->error('Unknown client type.');
            $this->line('Valid: '.implode(', ', OAuthClientProfile::TYPES));

            return self::FAILURE;
        }

        if ($type === OAuthClientProfile::TYPE_POS_DEVICE) {
            $this->error('pos_device clients need the device grant, which stays disabled (SEC-002).');

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: $type.' client');
        $tenantId = $this->option('tenant') !== null ? (string) $this->option('tenant') : null;

        if ($type === OAuthClientProfile::TYPE_PARTNER) {
            $tenant = $tenantId !== null ? Tenant::query()->find($tenantId) : null;
            if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
                $this->error('Partner clients require an active --tenant.');

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

        $secret = Str::random(48);

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
            'allowed_scopes' => [],
            'require_pkce' => true,
            'rate_tier' => 'standard',
        ]);
        $profile->save();

        $this->info('Client created: '.(string) $client->getKey());
        $this->line('Secret (shown once): '.$secret);
        $this->line('Grants: '.implode(', ', $grants));

        return self::SUCCESS;
    }
}
