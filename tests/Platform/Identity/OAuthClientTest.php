<?php

namespace Tests\Platform\Identity;

use App\Platform\Identity\OAuth\OAuthClientProfile;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * Passport P3a (TOKEN-001, SEC-002 §2.3): client profiles with tenant
 * binding, the client:create command (secret shown once, hashed at
 * rest), and per-client grant enforcement on the token route.
 */
final class OAuthClientTest extends TenantIntegrationTestCase
{
    private function partnerClient(string $tenantId): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'Partner integration',
            'secret' => 'partner-secret-12345',
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_PARTNER,
            'tenant_id' => $tenantId,
            'allowed_grants' => ['client_credentials'],
        ]);

        return $client;
    }

    public function test_command_creates_client_and_profile(): void
    {
        $tenant = $this->makeTenant();
        $tid = (string) $tenant->getKey();

        $exit = \Artisan::call('platform:client:create', [
            '--name' => 'Drill partner',
            '--type' => 'partner',
            '--tenant' => $tid,
        ]);
        $this->assertSame(0, $exit);

        $client = Client::query()->where('name', 'Drill partner')->firstOrFail();
        $this->assertDatabaseHas('oauth_client_profiles', [
            'client_id' => (string) $client->getKey(),
            'client_type' => 'partner',
            'tenant_id' => $tid,
        ], 'central');

        // Secret hashed at rest: the shown-once plaintext verifies, the
        // stored value is never the plaintext.
        $output = \Artisan::output();
        $this->assertStringContainsString('Secret (shown once): ', $output);
        preg_match('/Secret \(shown once\): (\S+)/', $output, $shown);
        $this->assertTrue(Hash::check($shown[1], $client->secret));
        $this->assertNotSame($shown[1], $client->secret);
    }

    public function test_command_rejects_partner_without_tenant(): void
    {
        \Artisan::call('platform:client:create', [
            '--name' => 'Orphan', '--type' => 'partner',
        ]);

        $this->assertDatabaseMissing('oauth_clients', ['name' => 'Orphan'], 'central');
    }

    public function test_disallowed_grant_denied_before_league(): void
    {
        $tenant = $this->makeTenant();
        $client = $this->partnerClient((string) $tenant->getKey());

        // Partner clients hold client_credentials only: an auth-code
        // redemption attempt fails on the grant, never reaching codes.
        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'partner-secret-12345',
            'redirect_uri' => 'https://example.test/callback',
            'code' => 'junk',
            'code_verifier' => 'junkjunkjunkjunkjunkjunkjunkjunk01',
        ])->assertStatus(400)->assertJsonPath('error', 'unsupported_grant');
    }

    public function test_allowed_grant_passes_middleware(): void
    {
        $tenant = $this->makeTenant();
        $client = $this->partnerClient((string) $tenant->getKey());

        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'partner-secret-12345',
            'scope' => '',
        ])->assertOk()->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    }

    public function test_profileless_client_rejected(): void
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'No profile',
            'secret' => 'profileless-secret-1',
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $client->save();

        $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'profileless-secret-1',
            'scope' => '',
        ])->assertStatus(401)->assertJsonPath('error', 'invalid_client');
    }
}
