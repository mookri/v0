<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use Laravel\Passport\Client;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * RFC 7009 revocation (SEC-015 §8.1): client-authenticated revocation of
 * access tokens (cascading to refresh tokens) and refresh tokens
 * (symmetric kill of the access token); unknown tokens still 200 (no
 * oracle); revoked access tokens fail dual-accept immediately.
 */
final class RevokeTokenTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    protected function setUp(): void
    {
        parent::setUp();

        // Reuse detection now records to the federation audit chain
        // (ADR-0020 D5), whose pseudonymization requires the identity
        // secret — kit convention: each test supplies its own.
        config(['identity.hmac_secret' => 'revoke-token-test-secret']);
    }

    /**
     * @return array{0: Client, 1: array{access_token: string, refresh_token: string}}
     */
    private function tokensFor(User $user): array
    {
        return $this->userTokensFor($user);
    }

    /**
     * @return array{0: string, 1: string} client id + secret
     */
    private function clientCredentials(Client $client): array
    {
        return [(string) $client->getKey(), self::CLIENT_SECRET];
    }

    public function test_revoke_access_cascades_and_bites_dual_accept(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$client, $tokens] = $this->tokensFor($user);
        [$id, $secret] = $this->clientCredentials($client);
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
        [$id, $secret] = $this->clientCredentials($client);
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
        [$id, $secret] = $this->clientCredentials($client);

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

    public function test_malformed_requests_answer_league_invalid_request(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        [$client] = $this->tokensFor($user);
        [$id, $secret] = $this->clientCredentials($client);
        auth()->logout();

        // Missing token: protocol surface answers invalid_request 400,
        // never the kernel 422 envelope.
        $this->postJson('/api/oauth/revoke', [
            'client_id' => $id,
            'client_secret' => $secret,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        // Bad hint value: same shape.
        $this->postJson('/api/oauth/revoke', [
            'token' => 'no-such-token',
            'token_type_hint' => 'bogus',
            'client_id' => $id,
            'client_secret' => $secret,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_request');

        // Missing client_secret: same shape (not 401 — the request never
        // reached client authentication).
        $this->postJson('/api/oauth/revoke', [
            'token' => 'no-such-token',
            'client_id' => $id,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_request');
    }
}
