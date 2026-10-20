<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\OAuthClientProfile;
use Laravel\Passport\Client;
use Tests\TenantIntegrationTestCase;

/**
 * The per-client PKCE mandate is enforced at the AUTHORIZE endpoint
 * (BACKEND-RCA 2026-10-21, finding 2.3).
 *
 * It used to be checked on the PAR push path only, so a confidential
 * client - which is every client this platform creates - could complete
 * a full authorization-code flow with no code challenge at all. League
 * only requires a challenge for *public* clients.
 */
final class PkceMandateTest extends TenantIntegrationTestCase
{
    private function webClient(bool $requirePkce): Client
    {
        $client = new Client();
        $client->forceFill([
            'name' => 'PKCE mandate client',
            'secret' => 'pkce-secret-12345',
            'redirect_uris' => ['https://example.test/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ]);
        $client->save();

        OAuthClientProfile::factory()->create([
            'client_id' => (string) $client->getKey(),
            'client_type' => OAuthClientProfile::TYPE_FIRST_PARTY_WEB,
            'tenant_id' => null,
            'allowed_grants' => ['authorization_code', 'refresh_token'],
            'require_pkce' => $requirePkce,
            'require_par' => false,
        ]);

        return $client;
    }

    /**
     * @param  array<string, string>  $extra
     */
    private function authorize(User $user, Client $client, array $extra = [])
    {
        return $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(array_merge([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => 'https://example.test/callback',
            'response_type' => 'code',
            'scope' => '',
            'state' => 'xyz',
        ], $extra)));
    }

    public function test_authorize_requires_a_challenge_when_the_profile_mandates_pkce(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $client = $this->webClient(true);

        $response = $this->authorize($user, $client);

        // The failure is delivered as an OAuth error, never as a
        // consent page: no authorization code may be issued.
        $this->assertTrue(
            $response->getStatusCode() === 302 || $response->getStatusCode() === 400,
            'The authorize endpoint must not render the consent page without a challenge.'
        );

        $this->assertStringNotContainsString(
            'name="code"',
            (string) $response->getContent(),
            'No authorization code may be issued without a challenge.'
        );

        $this->assertSame('invalid_request', $this->oauthError($response));
    }

    public function test_authorize_rejects_the_plain_challenge_method(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $client = $this->webClient(false);

        $response = $this->authorize($user, $client, [
            'code_challenge' => 'plain-challenge-value',
            'code_challenge_method' => 'plain',
        ]);

        $this->assertTrue(
            $response->getStatusCode() === 302 || $response->getStatusCode() === 400,
            'The plain challenge method must not reach the consent page.'
        );

        $this->assertSame('invalid_request', $this->oauthError($response));
    }

    /**
     * The OAuth error code of a failed authorize request, whether it
     * rode a redirect or a JSON body.
     */
    private function oauthError($response): ?string
    {
        $location = (string) $response->headers->get('Location');

        if ($location !== '') {
            $query = [];
            parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

            return $query['error'] ?? null;
        }

        $decoded = json_decode((string) $response->getContent(), true);

        return is_array($decoded) ? ($decoded['error'] ?? null) : null;
    }

    public function test_authorize_accepts_s256(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $client = $this->webClient(true);

        $this->authorize($user, $client, [
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'S256',
        ])->assertOk()->assertSee($client->name);
    }
}
