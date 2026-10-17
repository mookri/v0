<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * RFC 7662 introspection (SEC-015 §8.2): first-party service clients
 * only, active tokens (access + refresh) return claims, unknown /
 * expired / revoked tokens answer inactive without distinguishing,
 * bad client credentials 401.
 */
final class IntrospectTokenTest extends TenantIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 9c: the revoke leg now chains a token.revoked row whose
        // pseudonymization requires the identity secret.
        config(['identity.hmac_secret' => 'introspect-token-test-hmac-32!!!']);
    }

    /**
     * @return array{0: Client, 1: string} service client + secret
     */
    private function serviceClient(): array
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'Introspection service client',
            'secret' => 'introspect-secret-1',
            'redirect_uris' => [],
            'grant_types' => ['client_credentials'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_SERVICE,
            'allowed_grants' => ['client_credentials'],
        ]);

        return [$client, 'introspect-secret-1'];
    }

    /**
     * @return array{0: array{access_token: string, refresh_token: string}, 1: string} tokens + owning client id
     */
    private function userTokens(User $user): array
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'Introspect web client',
            'secret' => 'introspect-web-secret',
            'redirect_uris' => ['https://example.test/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
            'allowed_grants' => ['authorization_code', 'refresh_token'],
        ]);

        $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => 'https://example.test/callback',
            'response_type' => 'code', 'scope' => '', 'state' => 'xyz',
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'S256',
        ]))->assertOk();

        $approve = $this->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ])->assertStatus(302);
        parse_str((string) parse_url((string) $approve->headers->get('Location'), PHP_URL_QUERY), $query);

        $tokens = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'introspect-web-secret',
            'redirect_uri' => 'https://example.test/callback',
            'code' => $query['code'],
            'code_verifier' => $verifier,
        ])->assertOk()->json();

        return [$tokens, (string) $client->getKey()];
    }

    public function test_active_tokens_introspect_with_claims(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$service, $secret] = $this->serviceClient();
        [$tokens] = $this->userTokens($user);
        $auth = ['client_id' => (string) $service->getKey(), 'client_secret' => $secret];

        $access = $this->postJson('/api/oauth/introspect', $auth + ['token' => $tokens['access_token']])->assertOk();
        $access->assertJsonPath('active', true);
        $access->assertJsonPath('sub', (string) $user->getAuthIdentifier());
        $access->assertJsonPath('client_id', $access->json('client_id'));
        $this->assertNotEmpty($access->json('exp'));

        $refresh = $this->postJson('/api/oauth/introspect', $auth + ['token' => $tokens['refresh_token']])->assertOk();
        $refresh->assertJsonPath('active', true);
        $this->assertNotEmpty($refresh->json('exp'));
    }

    public function test_unknown_revoked_and_bad_clients(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$service, $secret] = $this->serviceClient();
        [$tokens, $webClientId] = $this->userTokens($user);
        $auth = ['client_id' => (string) $service->getKey(), 'client_secret' => $secret];

        // Garbage: inactive, no oracle.
        $this->postJson('/api/oauth/introspect', $auth + ['token' => 'garbage'])
            ->assertOk()->assertJsonPath('active', false);

        // Revoked access token: inactive. Revocation presents the
        // OWNING client's credentials — RFC 7009 §2.1 ownership (9c):
        // the service client could no longer revoke the web client's
        // token even if it tried.
        $this->postJson('/api/oauth/revoke', [
            'client_id' => $webClientId,
            'client_secret' => 'introspect-web-secret',
            'token' => $tokens['access_token'],
        ])->assertOk();
        $this->postJson('/api/oauth/introspect', $auth + ['token' => $tokens['access_token']])
            ->assertOk()->assertJsonPath('active', false);

        // Bad client secret: client authentication fails.
        $this->postJson('/api/oauth/introspect', [
            'token' => $tokens['refresh_token'],
            'client_id' => (string) $service->getKey(),
            'client_secret' => 'wrong',
        ])->assertStatus(401)->assertJsonPath('error', 'invalid_client');

        // Partner clients are not the introspection audience.
        $partner = new Client();
        $partner->forceFill([
            'name' => 'Partner', 'secret' => 'partner-secret-1', 'redirect_uris' => [],
            'grant_types' => ['client_credentials'], 'revoked' => false,
        ]);
        $partner->save();
        OAuthClientProfile::factory()->create([
            'client_id' => (string) $partner->getKey(),
            'client_type' => OAuthClientProfile::TYPE_PARTNER,
            'tenant_id' => (string) $tenant->getKey(),
            'allowed_grants' => ['client_credentials'],
        ]);

        $this->postJson('/api/oauth/introspect', [
            'token' => $tokens['refresh_token'],
            'client_id' => (string) $partner->getKey(),
            'client_secret' => 'partner-secret-1',
        ])->assertForbidden()->assertJsonPath('error', 'insufficient_scope');
    }

    public function test_malformed_requests_answer_league_invalid_request(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$service, $secret] = $this->serviceClient();
        $auth = ['client_id' => (string) $service->getKey(), 'client_secret' => $secret];
        auth()->logout();

        // Missing token: invalid_request 400, never the kernel 422.
        $this->postJson('/api/oauth/introspect', $auth)
            ->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        // Missing client_secret: same shape (pre-auth failure).
        $this->postJson('/api/oauth/introspect', [
            'token' => 'whatever',
            'client_id' => (string) $service->getKey(),
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_request');
    }
}
