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

    protected function oauthWebClient(string $name = 'Shared web client'): Client
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

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
            'allowed_grants' => ['authorization_code', 'refresh_token'],
        ]);

        return $client;
    }

    /**
     * @return array{0: Client, 1: array{access_token: string, refresh_token: string}}
     */
    protected function userTokensFor(User $user): array
    {
        $client = $this->oauthWebClient();

        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => 'https://example.test/callback',
            'response_type' => 'code',
            'scope' => '',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
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
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri' => 'https://example.test/callback',
            'code' => $query['code'],
            'code_verifier' => self::VERIFIER,
        ])->assertOk()->json();

        return [$client, $tokens];
    }
}
