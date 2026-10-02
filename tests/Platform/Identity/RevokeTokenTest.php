<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * RFC 7009 revocation (SEC-015 §8.1): client-authenticated revocation of
 * access tokens (cascading to refresh tokens) and refresh tokens
 * (symmetric kill of the access token); unknown tokens still 200 (no
 * oracle); revoked access tokens fail dual-accept immediately.
 */
final class RevokeTokenTest extends TenantIntegrationTestCase
{
    /**
     * @return array{0: Client, 1: array{access_token: string, refresh_token: string}}
     */
    private function tokensFor(User $user): array
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'Revoke web client',
            'secret' => 'revoke-secret-12345',
            'redirect_uris' => ['https://example.test/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ]);
        $client->save();

        \App\Platform\Identity\OAuth\OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => \App\Platform\Identity\OAuth\OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
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
            'client_secret' => 'revoke-secret-12345',
            'redirect_uri' => 'https://example.test/callback',
            'code' => $query['code'],
            'code_verifier' => $verifier,
        ])->assertOk()->json();

        return [$client, $tokens];
    }

    /**
     * @return array{0: string, 1: string} client id + secret
     */
    private function clientCredentials(Client $client, string $secret): array
    {
        return [(string) $client->getKey(), $secret];
    }

    public function test_revoke_access_cascades_and_bites_dual_accept(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$client, $tokens] = $this->tokensFor($user);
        [$id, $secret] = $this->clientCredentials($client, 'revoke-secret-12345');
        $tid = (string) $tenant->getKey();
        auth()->logout();

        $headers = ['Authorization' => 'Bearer '.$tokens['access_token'], 'X-Tenant-Id' => $tid];
        $this->getJson('/api/v1/catalog/items', $headers)->assertOk();

        $this->postJson('/api/oauth/revoke', [
            'token' => $tokens['access_token'],
            'client_id' => $id,
            'client_secret' => $secret,
        ])->assertOk();
        auth()->logout();

        // The access token is dead on dual-accept...
        $this->getJson('/api/v1/catalog/items', $headers)->assertUnauthorized();

        // ...and its refresh token died with it (no resurrection).
        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => $id,
            'client_secret' => $secret,
        ])->assertStatus(400);
    }

    public function test_revoke_refresh_kills_access_symmetrically(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$client, $tokens] = $this->tokensFor($user);
        [$id, $secret] = $this->clientCredentials($client, 'revoke-secret-12345');
        auth()->logout();

        $this->postJson('/api/oauth/revoke', [
            'token' => $tokens['refresh_token'],
            'token_type_hint' => 'refresh_token',
            'client_id' => $id,
            'client_secret' => $secret,
        ])->assertOk();
        auth()->logout();

        $this->getJson('/api/v1/catalog/items', [
            'Authorization' => 'Bearer '.$tokens['access_token'],
            'X-Tenant-Id' => (string) $tenant->getKey(),
        ])->assertUnauthorized();
    }

    public function test_unknown_tokens_still_200_and_bad_clients_401(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$client] = $this->tokensFor($user);
        [$id, $secret] = $this->clientCredentials($client, 'revoke-secret-12345');

        // No oracle: unknown tokens succeed silently.
        $this->postJson('/api/oauth/revoke', [
            'token' => 'no-such-token',
            'client_id' => $id,
            'client_secret' => $secret,
        ])->assertOk();

        // Wrong secret: client authentication fails (league shape).
        $this->postJson('/api/oauth/revoke', [
            'token' => 'no-such-token',
            'client_id' => $id,
            'client_secret' => 'wrong',
        ])->assertStatus(401)->assertJsonPath('error', 'invalid_client');
    }
}
