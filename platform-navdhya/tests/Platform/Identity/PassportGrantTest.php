<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\TokenReuseDetected;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * Passport P2 (TOKEN-001, SEC-002/003): code + PKCE happy path, PKCE
 * mismatch, refresh rotation with reuse detection (lineage kill +
 * security event), banned grants, token lifetime conformance.
 */
final class PassportGrantTest extends TenantIntegrationTestCase
{
    private function webClient(): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'Test web client',
            'secret' => 'test-secret-12345',
            'redirect_uris' => ['https://example.test/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ]);
        $client->save();

        \App\Platform\Identity\OAuth\OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => \App\Platform\Identity\OAuth\OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
            'tenant_id' => null,
            'allowed_grants' => ['authorization_code', 'refresh_token'],
        ]);

        return $client;
    }

    /**
     * @return array{0: string, 1: string} auth code + verifier
     */
    private function authorizeCode(User $user, Client $client, ?string $challenge = null): array
    {
        $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
        $challenge ??= 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';

        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => 'https://example.test/callback',
            'response_type' => 'code',
            'scope' => '',
            'state' => 'xyz',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]))->assertOk()->assertSee($client->name);

        $approve = $this->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ])->assertStatus(302);

        $location = (string) $approve->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('code', $query);

        return [$query['code'], $verifier];
    }

    /**
     * @return array<string, mixed>
     */
    private function token(array $payload): array
    {
        return $this->postJson('/oauth/token', $payload)->json();
    }

    public function test_code_pkce_happy_path_and_ttl(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $client = $this->webClient();
        [$code, $verifier] = $this->authorizeCode($user, $client);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'test-secret-12345',
            'redirect_uri' => 'https://example.test/callback',
            'code' => $code,
            'code_verifier' => $verifier,
        ])->assertOk();

        $tokens = $response->json();
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);

        // SEC-003: 15-minute access maximum (exp - iat == 900, sub-second
        // float skew from key issuance).
        $payload = json_decode(base64_decode(explode('.', $tokens['access_token'])[1]), true);
        $this->assertEqualsWithDelta(900, $payload['exp'] - $payload['iat'], 1.0);
    }

    public function test_pkce_mismatch_rejected(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $client = $this->webClient();
        [$code] = $this->authorizeCode($user, $client);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'test-secret-12345',
            'redirect_uri' => 'https://example.test/callback',
            'code' => $code,
            'code_verifier' => 'wrong-verifier-wrong-verifier-wrong01',
        ])->assertStatus(400);
    }

    public function test_refresh_rotation_and_reuse_kills_lineage(): void
    {
        Event::fake([TokenReuseDetected::class]);

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $client = $this->webClient();
        [$code, $verifier] = $this->authorizeCode($user, $client);

        $first = $this->token([
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'test-secret-12345',
            'redirect_uri' => 'https://example.test/callback',
            'code' => $code,
            'code_verifier' => $verifier,
        ]);

        // Rotation: the presented refresh token dies with use.
        $second = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $first['refresh_token'],
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'test-secret-12345',
        ])->assertOk()->json();
        $this->assertNotSame($first['refresh_token'], $second['refresh_token']);

        // Reuse of the rotated token fails closed...
        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $first['refresh_token'],
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'test-secret-12345',
        ])->assertStatus(400);

        // ...kills the whole lineage (the replacement access token row is
        // revoked too) and emits the security event.
        Event::assertDispatched(TokenReuseDetected::class);
        $this->assertDatabaseMissing('oauth_access_tokens', [
            'revoked' => false,
            'client_id' => (string) $client->getKey(),
        ], 'central');
    }

    public function test_password_grant_stays_banned(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $client = $this->webClient();

        // Even a fully-profiled client cannot use the password grant:
        // it is in no profile's allowed set (server-wide ban).
        $this->postJson('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => (string) $client->getKey(),
            'client_secret' => 'test-secret-12345',
            'username' => 'a@example.test',
            'password' => 'secret',
            'scope' => '',
        ])->assertStatus(400)->assertJsonPath('error', 'unsupported_grant');
    }
}
