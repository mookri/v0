<?php

namespace Tests\Platform\Identity;

use App\Models\User;
use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\Federation\Models\AuthEvent;
use App\Platform\Identity\OAuth\Backchannel\SessionBindingRecorder;
use App\Platform\Identity\OAuth\IssuerIdentifier;
use App\Platform\Identity\OAuth\OidcBackchannelDelivery;
use App\Platform\Identity\OAuth\OidcSessionBinding;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use Laravel\Passport\Client;
use Tests\Platform\Identity\Concerns\IssuesUserTokens;
use Tests\Support\InteractsWithMsg91;
use Tests\TenantIntegrationTestCase;

/**
 * A-SEC-004-5: OIDC RP-Initiated Logout 1.0 — /oauth/logout
 * (discovery end_session_endpoint) terminates the OP session
 * unconditionally through the shared SessionTerminator, redirects only
 * to an EXACT registered post-logout URI of the client a validated
 * hint (or a bare client_id when no hint was sent) identifies, accepts
 * expired hints by design, and never redirects on a failed hint —
 * landing on the neutral logged-out page instead. Back-channel
 * dispatch rides the same termination.
 */
final class EndSessionTest extends TenantIntegrationTestCase
{
    use InteractsWithMsg91;
    use IssuesUserTokens;

    private const HOST = 'http://auth.localhost';

    private const REDIRECT = 'https://example.test/callback';

    private const POST_LOGOUT = 'https://example.test/logged-out';

    private const BACKCHANNEL = 'https://rp.test/backchannel';

    private const SID = '01JENDSESSIONTESTSESSION00001';

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

    private function oidcClient(bool $withBackchannel = false): Client
    {
        $profile = [
            'allowed_scopes' => ['openid'],
            'post_logout_redirect_uris' => [self::POST_LOGOUT],
        ];

        if ($withBackchannel) {
            $profile['backchannel_logout_uri'] = self::BACKCHANNEL;
        }

        return $this->oauthWebClient('OIDC logout client', $profile);
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
            'nonce' => 'endsession-nonce-1',
        ], $overrides);
    }

    /**
     * A REAL id_token through the full flow (authorize -> consent ->
     * exchange) with a seeded ceremony sid, exactly as an RP would hold
     * one when it starts RP-initiated logout.
     */
    private function mintIdToken(Client $client, User $user): string
    {
        $this->withSession(['oidc.ceremony' => [
            'auth_time' => time(),
            'amr' => ['otp'],
            'acr' => 'aal1',
            'sid' => self::SID,
        ]]);

        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query(
            $this->authorizeParams($client)
        ))->assertOk();

        $approve = $this->post('/oauth/authorize', [
            '_token' => csrf_token(),
            'state' => 'xyz',
            'client_id' => (string) $client->getKey(),
            'auth_token' => session('authToken'),
        ]);
        $approve->assertStatus(302);
        parse_str((string) parse_url((string) $approve->headers->get('Location'), PHP_URL_QUERY), $query);

        $tokens = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) $client->getKey(),
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri' => self::REDIRECT,
            'code' => (string) $query['code'],
            'code_verifier' => self::VERIFIER,
        ])->assertOk()->json();

        return (string) $tokens['id_token'];
    }

    /**
     * A structurally valid, correctly signed id_token_hint whose exp
     * lies in the past — RP-Initiated Logout MUST accept these (the
     * 10-minute ID-token window cannot gate a later sign-out).
     */
    private function expiredHint(Client $client, User $user): string
    {
        $sub = $user->external_id !== null && $user->external_id !== ''
            ? (string) $user->external_id
            : (string) $user->getAuthIdentifier();

        $now = new DateTimeImmutable();

        return (new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates()))
            ->issuedBy(IssuerIdentifier::value())
            ->relatedTo($sub)
            ->permittedFor((string) $client->getKey())
            ->issuedAt($now->modify('-1 day'))
            ->expiresAt($now->modify('-1 hour'))
            ->withClaim('auth_time', $now->modify('-1 day')->getTimestamp())
            ->getToken(new Sha256(), InMemory::file('file://'.storage_path('oauth-private.key')))
            ->toString();
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} header, claims
     */
    private function decodeJwt(string $jwt): array
    {
        $segments = explode('.', $jwt);
        $this->assertCount(3, $segments);

        $header = json_decode($this->base64UrlDecode($segments[0]), true);
        $claims = json_decode($this->base64UrlDecode($segments[1]), true);

        $this->assertIsArray($header);
        $this->assertIsArray($claims);

        return [$header, $claims];
    }

    private function base64UrlDecode(string $segment): string
    {
        $base64 = strtr($segment, '-_', '+/');
        $padded = $base64.str_repeat('=', (4 - strlen($base64) % 4) % 4);

        return (string) base64_decode($padded, true);
    }

    public function test_valid_hint_redirects_to_the_registered_uri_and_terminates(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $hint = $this->mintIdToken($client, $user);
        Http::fake();

        $res = $this->get(self::HOST.'/oauth/logout?'.http_build_query([
            'id_token_hint' => $hint,
            'post_logout_redirect_uri' => self::POST_LOGOUT,
            'state' => 'bye',
        ]));

        $res->assertStatus(302);
        $this->assertSame(self::POST_LOGOUT.'?state=bye', (string) $res->headers->get('Location'));

        // The OP session is gone…
        $this->assertNull(session('oidc.ceremony'));

        // …recorded through the shared terminator with the RP-initiated
        // reason on the hash-chained chain.
        $this->assertSame(1, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.logout')
            ->where('decision', 'allow')
            ->where('reason_code', 'rp_initiated')
            ->where('subject_ref', AuthEventRecorder::ref('sub', (string) $user->getAuthIdentifier()))
            ->count());
    }

    public function test_invalid_hint_signature_terminates_without_redirecting(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $hint = $this->mintIdToken($client, $user);

        // Break the signature: same shape, zero trust.
        $segments = explode('.', $hint);
        $tampered = $segments[0].'.'.$segments[1].'.'.strrev($segments[2]);
        Http::fake();

        $res = $this->get(self::HOST.'/oauth/logout?'.http_build_query([
            'id_token_hint' => $tampered,
            'post_logout_redirect_uri' => self::POST_LOGOUT,
            'state' => 'bye',
        ]));

        // Session still ends (the user asked), the redirect never
        // happens (the requester was not identified), the deny lands.
        $res->assertOk()->assertViewIs('oauth.logged-out');
        $this->assertNull(session('oidc.ceremony'));
        $this->assertSame(1, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.end_session_request')
            ->where('decision', 'deny')
            ->where('reason_code', 'invalid_id_token_hint')
            ->count());
    }

    public function test_unregistered_post_logout_uri_never_redirects(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $hint = $this->mintIdToken($client, $user);
        Http::fake();

        $res = $this->get(self::HOST.'/oauth/logout?'.http_build_query([
            'id_token_hint' => $hint,
            'post_logout_redirect_uri' => 'https://evil.test/landing',
        ]));

        $res->assertOk()->assertViewIs('oauth.logged-out');
        $this->assertNull(session('oidc.ceremony'));
    }

    public function test_client_id_fallback_redirects_without_a_hint(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $this->withSession(['oidc.ceremony' => ['auth_time' => time(), 'sid' => self::SID]]);
        $this->actingAs($user);

        $res = $this->get(self::HOST.'/oauth/logout?'.http_build_query([
            'client_id' => (string) $client->getKey(),
            'post_logout_redirect_uri' => self::POST_LOGOUT,
        ]));

        $res->assertStatus(302);
        $this->assertSame(self::POST_LOGOUT, (string) $res->headers->get('Location'));
        $this->assertNull(session('oidc.ceremony'));
    }

    public function test_expired_hint_is_still_accepted(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $hint = $this->expiredHint($client, $user);
        Http::fake();

        $res = $this->get(self::HOST.'/oauth/logout?'.http_build_query([
            'id_token_hint' => $hint,
            'post_logout_redirect_uri' => self::POST_LOGOUT,
        ]));

        // exp is deliberately NOT consulted: a valid signature over the
        // issuer's key + registered target is all the trust needed.
        $res->assertStatus(302);
        $this->assertSame(self::POST_LOGOUT, (string) $res->headers->get('Location'));
    }

    public function test_guest_request_terminates_as_a_noop_on_the_neutral_page(): void
    {
        $res = $this->get(self::HOST.'/oauth/logout');

        $res->assertOk()->assertViewIs('oauth.logged-out');

        // No session, no user: nothing to audit, nothing to propagate.
        $this->assertSame(0, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.logout')
            ->count());
    }

    public function test_end_session_dispatches_back_channel_logout_for_bound_rps(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient(withBackchannel: true);
        $hint = $this->mintIdToken($client, $user);

        SessionBindingRecorder::record(self::SID, (string) $client->getKey(), (int) $user->getAuthIdentifier());
        Http::fake();

        $this->get(self::HOST.'/oauth/logout?'.http_build_query([
            'id_token_hint' => $hint,
            'post_logout_redirect_uri' => self::POST_LOGOUT,
        ]))->assertStatus(302);

        $captured = null;

        Http::assertSent(function ($request) use (&$captured): bool {
            if ($request->url() !== self::BACKCHANNEL) {
                return false;
            }

            $captured = (string) $request['logout_token'];

            return $request->isForm();
        });

        $this->assertIsString($captured);

        [$header, $claims] = $this->decodeJwt($captured);

        // Spec shape: logout+jwt, kid resolvable against the published
        // JWKS, session-bound claims, the mandatory events registry.
        $this->assertSame('logout+jwt', $header['typ']);
        $jwks = $this->getJson('/.well-known/jwks.json')->assertOk()->json('keys');
        $this->assertSame($jwks[0]['kid'], $header['kid']);
        $this->assertSame(IssuerIdentifier::value(), $claims['iss']);
        $this->assertSame((string) $client->getKey(), $claims['aud']);
        $this->assertSame(self::SID, $claims['sid']);
        $this->assertArrayHasKey('http://schemas.openid.net/event/backchannel-logout', $claims['events']);
        $this->assertSame(120, $claims['exp'] - $claims['iat']);
        $this->assertNotEmpty($claims['jti']);
        $this->assertNotEmpty($claims['sub']);

        // Delivery + binding lifecycle: delivered once, binding
        // notified, chain rows for both the logout and the push.
        $delivery = OidcBackchannelDelivery::query()->where('sid', self::SID)->firstOrFail();
        $this->assertSame('delivered', $delivery->state);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->delivered_at);

        $binding = OidcSessionBinding::query()->where('sid', self::SID)->firstOrFail();
        $this->assertNotNull($binding->notified_at);

        $this->assertSame(1, AuthEvent::query()->withoutGlobalScopes()
            ->where('event_type', 'session.backchannel_logout')
            ->where('decision', 'allow')
            ->where('reason_code', 'delivered')
            ->where('session_ref', AuthEventRecorder::ref('sess', self::SID))
            ->where('actor_client_id', (string) $client->getKey())
            ->count());
    }

    public function test_discovery_advertises_end_session_and_back_channel_support(): void
    {
        $this->getJson('/.well-known/openid-configuration')
            ->assertOk()
            ->assertJsonPath('end_session_endpoint', 'http://auth.localhost/oauth/logout')
            ->assertJsonPath('backchannel_logout_supported', true)
            ->assertJsonPath('backchannel_logout_session_supported', true);

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('end_session_endpoint', 'http://auth.localhost/oauth/logout');
    }
}
