<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\OAuth\IssuerIdentifier;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * A-SEC-004-5: OIDC max_age at the authorize endpoint — the ISSUER
 * decides session freshness (session auth_time), never the RP:
 *
 *  - stale or UNPROVABLE freshness parks a bounded oidc.reauth
 *    challenge and bounces to the unified ceremony (login page
 *    signed-in shortcut suspended), resuming via url.intended,
 *  - a completed ceremony discharges the challenge and the fresh
 *    auth_time passes,
 *  - prompt=none converts the stale session into a login_required
 *    error redirect (interaction forbidden),
 *  - malformed max_age fails invalid_request — and non-openid
 *    requests never see the parameter (it is OIDC-only),
 *  - every error redirect carries state + RFC 9207 iss.
 */
final class MaxAgeEnforcementTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;
    use IssuesUserTokens;

    private const HOST = 'http://auth.localhost';

    private const REDIRECT = 'https://example.test/callback';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeMsg91();

        config([
            'federation.domain' => 'auth.localhost',
            'federation.issuer' => 'http://auth.localhost',
            'federation.mode' => 'federated',
            'identity.hmac_secret' => 'test-hmac-secret-for-federation-32-chars!!',
        ]);
    }

    /**
     * @param  array<string, mixed>  $profileOverrides
     */
    private function oidcClient(array $profileOverrides = []): Client
    {
        return $this->oauthWebClient('OIDC web client', array_merge([
            'allowed_scopes' => ['openid'],
        ], $profileOverrides));
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
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
            'nonce' => 'maxage-nonce-1',
        ], $overrides);
    }

    /**
     * @return array<string, mixed> the stashed ceremony shape
     */
    private function staleCeremony(): array
    {
        return [
            'auth_time' => time() - 7200,
            'amr' => ['otp'],
            'acr' => 'aal1',
            'sid' => '01JMAXAGESTALESESSION0000001',
        ];
    }

    private function startOtp(string $phone): string
    {
        $res = $this->postJson(self::HOST.'/auth/login/otp', ['phone' => $phone]);
        $res->assertStatus(202);

        return (string) $res->json('data.challenge_id');
    }

    private function loginViaOtp(string $phone): TestResponse
    {
        return $this->postJson(self::HOST.'/auth/login/verify', [
            'challenge_id' => $this->startOtp($phone),
            'token' => $this->msg91TokenFor($phone),
        ]);
    }

    public function test_stale_session_bounces_to_the_ceremony_with_a_bounded_challenge(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $this->withSession(['oidc.ceremony' => $this->staleCeremony()]);

        $params = $this->authorizeParams($client, ['max_age' => '600']);
        ksort($params);
        $authorizeUrl = self::HOST.'/oauth/authorize?'.http_build_query($params);

        $res = $this->actingAs($user)->get($authorizeUrl);

        $res->assertStatus(302);
        $this->assertStringEndsWith('/auth/login', (string) $res->headers->get('Location'));

        // The resume target is pinned for the ceremony's return leg…
        $this->assertSame($authorizeUrl, session('url.intended'));

        // …and the bounded challenge suspends the login page's
        // signed-in shortcut until a fresh ceremony completes.
        $reauth = session('oidc.reauth');
        $this->assertIsArray($reauth);
        $this->assertGreaterThan(time(), (int) ($reauth['until'] ?? 0));
        $this->assertLessThanOrEqual(time() + 300, (int) $reauth['until']);
    }

    public function test_fresh_session_passes_and_discharges_a_parked_challenge(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $this->withSession([
            'oidc.ceremony' => [
                'auth_time' => time() - 30,
                'amr' => ['otp'],
                'acr' => 'aal1',
                'sid' => '01JMAXAGEFRESHSESSION00000001',
            ],
            'oidc.reauth' => ['until' => time() + 300],
        ]);

        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['max_age' => '600'])
        ))->assertOk();

        // Requirement satisfied => the parked challenge is gone; the
        // login page works normally again.
        $this->assertNull(session('oidc.reauth'));
    }

    public function test_unprovable_freshness_fails_closed_to_re_authentication(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        // Authenticated, but the session carries NO ceremony facts:
        // there is no auth_time to measure, so max_age cannot be
        // satisfied — no session shortcut is trusted here.
        $res = $this->actingAs($user)->get(self::HOST.'/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['max_age' => '600'])
        ));

        $res->assertStatus(302);
        $this->assertStringEndsWith('/auth/login', (string) $res->headers->get('Location'));
        $this->assertIsArray(session('oidc.reauth'));
    }

    public function test_prompt_none_reports_login_required_instead_of_bouncing(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $this->withSession(['oidc.ceremony' => $this->staleCeremony()]);

        $res = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['max_age' => '600', 'prompt' => 'none'])
        ));

        $res->assertStatus(302);
        $location = (string) $res->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('login_required', $query['error']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);

        // No interaction happened: no challenge was parked either.
        $this->assertNull(session('oidc.reauth'));
    }

    public function test_malformed_max_age_fails_invalid_request_with_iss(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $this->withSession(['oidc.ceremony' => $this->staleCeremony()]);

        $res = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['max_age' => 'soon'])
        ));

        $res->assertStatus(302);
        $location = (string) $res->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('invalid_request', $query['error']);
        $this->assertStringContainsString('non-negative integer', (string) $query['error_description']);
        $this->assertSame('xyz', $query['state']);
        $this->assertSame(IssuerIdentifier::value(), $query['iss']);

        // A malformed parameter is a rejection, not a re-auth trigger.
        $this->assertNull(session('oidc.reauth'));
    }

    public function test_non_openid_requests_ignore_max_age_entirely(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        // Pure OAuth (no openid scope): no auth_time concept exists, so
        // even a malformed max_age passes untouched — exactly how an
        // unknown parameter behaves.
        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client, ['scope' => '', 'max_age' => 'soon', 'nonce' => ''])
        ))->assertOk();
    }

    public function test_completed_ceremony_resumes_the_authorize_request_fresh(): void
    {
        $client = $this->oidcClient();
        $phone = '9898989864';

        $this->withSession(['oidc.ceremony' => $this->staleCeremony()]);

        $params = $this->authorizeParams($client, ['max_age' => '300']);
        ksort($params);
        $authorizeUrl = self::HOST.'/oauth/authorize?'.http_build_query($params);

        // Stale: bounced to the ceremony with the challenge parked.
        $bounce = $this->get($authorizeUrl);
        $bounce->assertStatus(302);
        $this->assertStringEndsWith('/auth/login', (string) $bounce->headers->get('Location'));
        $this->assertIsArray(session('oidc.reauth'));

        // The OTP ceremony completes: fresh auth_time stashed, the
        // challenge discharged, the resume target handed back.
        $login = $this->loginViaOtp($phone);
        $login->assertOk();
        $resume = (string) $login->json('data.redirect');
        $this->assertStringStartsWith('/oauth/authorize?', $resume);
        $this->assertNull(session('oidc.reauth'));

        $ceremony = session('oidc.ceremony');
        $this->assertIsArray($ceremony);
        $this->assertGreaterThan(time() - 60, (int) $ceremony['auth_time']);

        // Resume passes enforcement and renders consent.
        $this->get(self::HOST.$resume)->assertOk();
    }

    public function test_login_page_suspends_the_signed_in_shortcut_while_challenged(): void
    {
        $user = User::factory()->create();
        $next = '/oauth/authorize?client_id=stateless';

        $this->withSession(['oidc.ceremony' => [
            'auth_time' => time() - 10,
            'amr' => ['otp'],
            'acr' => 'aal1',
            'sid' => '01JMAXAGESHORTCUT00000000001',
        ]]);

        // Signed in, no challenge: the shortcut returns the browser to
        // the resume target immediately (pre-existing behavior;
        // redirect()->to() resolves the relative target against the
        // request root).
        $this->actingAs($user)
            ->get(self::HOST.'/auth/login?'.http_build_query(['next' => $next]))
            ->assertStatus(302)
            ->assertHeader('Location', self::HOST.$next);

        // With a parked challenge: the same request renders the login
        // page instead — user state withheld (null) so the ceremony
        // UI is what the browser sees, not a "continue as" shortcut.
        $this->withSession(['oidc.reauth' => ['until' => time() + 300]]);

        $this->actingAs($user)
            ->get(self::HOST.'/auth/login?'.http_build_query(['next' => $next]))
            ->assertOk()
            ->assertViewIs('federation.login')
            ->assertViewHas('user', null);
    }
}
