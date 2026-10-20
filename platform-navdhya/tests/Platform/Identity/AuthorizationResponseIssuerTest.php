<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\IssuerIdentifier;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\TenantIntegrationTestCase;

/**
 * 9a (ADR-0021 D2) RFC 9207: every redirect-based authorization
 * response — approval, consent denial, prompt=none login_required —
 * carries the issuer identifier byte-identical to discovery, so a BFF
 * callback can bind the response to the AS it initiated the flow with.
 * The federation login bounce (no protocol outcome) is never
 * issuer-bound: it stays exactly as issued.
 */
final class AuthorizationResponseIssuerTest extends TenantIntegrationTestCase
{
    use IssuesUserTokens;

    private const HOST = 'http://auth.localhost';

    private const REDIRECT = 'https://example.test/callback';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
            'federation.mode' => 'federated',
            'identity.hmac_secret' => 'test-hmac-secret-for-federation-32-chars!!',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function authorizeParams(Client $client, array $overrides = []): array
    {
        return array_merge([
            'client_id' => (string) $client->getKey(),
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'scope' => '',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
        ], $overrides);
    }

    /**
     * Assert a 302 to the registered redirect and return its parsed
     * query — the shared shape for every issuer-bound outcome.
     *
     * @return array<string, mixed>
     */
    private function redirectQuery(TestResponse $response): array
    {
        $response->assertStatus(302);
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return $query;
    }

    public function test_approval_redirect_carries_the_exact_issuer(): void
    {
        $user = User::factory()->create();
        $client = $this->oauthWebClient();

        $this->actingAs($user)
            ->get(self::HOST.'/oauth/authorize?'.http_build_query($this->authorizeParams($client)))
            ->assertOk();

        $query = $this->redirectQuery($this->post(self::HOST.'/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ]));

        $this->assertNotEmpty($query['code']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame('http://auth.localhost', $query['iss']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
    }

    public function test_denial_redirect_carries_the_exact_issuer(): void
    {
        $user = User::factory()->create();
        $client = $this->oauthWebClient();

        $this->actingAs($user)
            ->get(self::HOST.'/oauth/authorize?'.http_build_query($this->authorizeParams($client)))
            ->assertOk();

        $query = $this->redirectQuery($this->delete(self::HOST.'/oauth/authorize?'.http_build_query([
            'auth_token' => session('authToken'),
        ])));

        $this->assertSame('access_denied', $query['error']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
    }

    public function test_prompt_none_login_required_redirect_carries_the_issuer(): void
    {
        $client = $this->oauthWebClient();

        // Guest + prompt=none: login_required is delivered to the
        // registered URI (never bounced to the login page) — and is
        // still an issuer-bound protocol response.
        $res = $this->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['prompt' => 'none'])
        ));

        $query = $this->redirectQuery($res);
        $this->assertSame('login_required', $query['error']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);
    }

    public function test_login_bounce_carries_no_issuer_and_no_protocol_outcome(): void
    {
        $client = $this->oauthWebClient();

        $params = $this->authorizeParams($client);
        // Symfony's fullUrl() (what redirect()->guest() stashes as the
        // intended URL) rebuilds the query with sorted keys — build the
        // canonical form so comparisons are exact.
        ksort($params);
        $authorizeUrl = self::HOST.'/oauth/authorize?'.http_build_query($params);

        $bounce = $this->get($authorizeUrl);
        $bounce->assertStatus(302);
        $location = (string) $bounce->headers->get('Location');
        $this->assertStringEndsWith('/auth/login', $location);
        $this->assertSame($authorizeUrl, session('url.intended'));

        // A login bounce is not a protocol response: no code, no error,
        // no iss — AppendsIssuer only ever binds code/error outcomes.
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame([], $query);
    }
}
