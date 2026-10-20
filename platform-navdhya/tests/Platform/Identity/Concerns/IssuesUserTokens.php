<?php

namespace Tests\Platform\Identity\Concerns;

use App\Models\User;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use Laravel\Passport\Client;

/**
 * Shared OAuth user-token minting for identity tests: profiled web
 * client, PKCE authorize + approve, code exchange. Keeps the ceremony
 * in one place — every token-flow test uses this, never a local copy.
 */
trait IssuesUserTokens
{
    protected const VERIFIER = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

    protected const CHALLENGE = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';

    protected const CLIENT_SECRET = 'shared-test-secret-1';

    /**
     * @param  array<string, mixed>  $profileOverrides
     */
    protected function oauthWebClient(string $name = 'Shared web client', array $profileOverrides = []): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => $name,
            'secret' => self::CLIENT_SECRET,
            'redirect_uris' => ['https://example.test/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create(array_merge([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
            'allowed_grants' => ['authorization_code', 'refresh_token'],
        ], $profileOverrides));

        return $client;
    }

    /**
     * PKCE authorize + approve + code exchange. The client defaults to
     * a fresh profiled web client; pass one to reuse an existing client
     * (e.g. after registering its auth key). `$exchangeExtra` merges
     * into the token request — supplying `client_assertion` switches
     * the exchange from the shared secret to RFC 7523 client
     * authentication (the secret is dropped so no fallback exists).
     * `$authorizeOverrides` merges into the authorize query (e.g.
     * `scope=openid` + `nonce` for the OIDC ceremony). `$exchangeHeaders`
     * carries extra token-request headers (the `DPoP` header of
     * A-SEC-004-9).
     *
     * @param  array<string, mixed>  $exchangeExtra
     * @param  array<string, mixed>  $authorizeOverrides
     * @param  array<string, string>  $exchangeHeaders
     * @return array{0: Client, 1: array{access_token: string, refresh_token: string}}
     */
    protected function userTokensFor(User $user, ?Client $existingClient = null, array $exchangeExtra = [], array $authorizeOverrides = [], array $exchangeHeaders = []): array
    {
        [$client, $response] = $this->userTokensAttempt($user, $existingClient, $exchangeExtra, $authorizeOverrides, $exchangeHeaders);

        return [$client, $response->assertOk()->json()];
    }

    /**
     * The same ceremony WITHOUT asserting the exchange outcome —
     * token-endpoint denial tests (DPoP mandate, invalid proof) need
     * the 4xx body, not a failed assertOk. userTokensFor() is exactly
     * this plus assertOk, so the ceremony stays single-sourced.
     *
     * @param  array<string, mixed>  $exchangeExtra
     * @param  array<string, mixed>  $authorizeOverrides
     * @param  array<string, string>  $exchangeHeaders
     * @return array{0: Client, 1: \Illuminate\Testing\TestResponse}
     */
    protected function userTokensAttempt(User $user, ?Client $existingClient = null, array $exchangeExtra = [], array $authorizeOverrides = [], array $exchangeHeaders = []): array
    {
        $client = $existingClient ?? $this->oauthWebClient();

        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(array_merge([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => 'https://example.test/callback',
            'response_type' => 'code',
            'scope' => '',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
        ], $authorizeOverrides)))->assertOk();

        $approve = $this->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ])->assertStatus(302);
        parse_str((string) parse_url((string) $approve->headers->get('Location'), PHP_URL_QUERY), $query);

        $body = [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri' => 'https://example.test/callback',
            'code' => $query['code'],
            'code_verifier' => self::VERIFIER,
        ];

        if (isset($exchangeExtra['client_assertion'])) {
            unset($body['client_secret']);
        }

        return [$client, $this->postJson('/oauth/token', array_merge($body, $exchangeExtra), $exchangeHeaders)];
    }
}
